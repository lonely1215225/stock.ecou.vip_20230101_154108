<?php
namespace app\cli\server;

use think\console\input\Argument;
use think\console\Command;
use think\console\input\Option;
use think\console\Output;
use think\console\Input;
use think\facade\Env;
use Swoole\Process;
use util\SysWsRedis;
use util\RedisUtil;

/**
 * 系统 WebSocket 服务器
 * --  以WebSocket服务方式，为后台提供推送等服务
 *
 * @package app\cli\server
 */
class SysWs extends Command
{

    /**
     * WebSocket Server 实例
     *
     * @var \swoole_websocket_server $server
     */
    protected $server;
    protected $serverHost = '0.0.0.0';
    protected $serverPort = 30201;
    protected $config;
    protected $socket;

    protected function configure()
    {
        $this->setName('ws:admin')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload", 'start')
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the swoole server in daemon mode.')
            ->setDescription('系统Websocket服务');
    }

    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');

        $this->init();

        // 执行服务起停动作
        if (in_array($action, ['start', 'stop', 'reload', 'restart'])) {
            $this->$action();
        } else {
            $output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload .</error>");
        }
    }

    /**
     * 初始化Server配置
     */
    public function init()
    {
        $this->config['worker_num'] = 1;
        $this->config['daemonize']  = false;
        $this->config['pid_file']   = Env::get('runtime_path') . "swoole_server_{$this->serverPort}.pid";
        $this->config['log_file']   = Env::get('runtime_path') . "swoole_server_{$this->serverPort}.log";
    }

    /**
     * 启动Server
     */
    public function start()
    {
        // 判断是否已经运行
        $pid = $this->getMasterPid();
        if ($this->isRunning($pid)) {
            $this->output->writeln("<error>swoole http server process is already running. PID:{$pid}</error>");

            return false;
        }

        // 启动服务
        $this->server = new \swoole_websocket_server($this->serverHost, $this->serverPort);

        // 是否开启守护进程模式
        if ($this->input->hasOption('daemon')) {
            $this->config['daemonize'] = true;
        }

        // 设置 server 运行前各项参数
        $this->server->set($this->config);

        // 设置WebSocket服务的回调函数
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('Open', [$this, 'onOpen']);
        $this->server->on('Message', [$this, 'onMessage']);
        $this->server->on('Close', [$this, 'onClose']);

        $this->output->writeln("Swoole http server started: <http://{$this->serverHost}:{$this->serverPort}>");
        if ($this->config['daemonize'] == false) $this->output->writeln('You can exit with <info>`CTRL-C`</info>');

        // 启动WebSocket服务
        $this->server->start();
    }

    /**
     * 停止server
     */
    public function stop()
    {
        $pid = $this->getMasterPid();

        if (!$this->isRunning($pid)) {
            $this->output->writeln('<error>no swoole http server process running.</error>');

            return false;
        }

        $this->output->writeln('Stopping swoole http server...');

        Process::kill($pid, SIGTERM);
        $this->removePid();

        $this->output->writeln('> success');
    }

    /**
     * 柔性重启server
     */
    public function reload()
    {
        $pid = $this->getMasterPid();

        if (!$this->isRunning($pid)) {
            $this->output->writeln('<error>no swoole http server process running.</error>');

            return false;
        }

        $this->output->writeln('Reloading swoole http server...');
        Process::kill($pid, SIGUSR1);
        $this->output->writeln('> success');
    }

    /**
     * 重启server
     */
    public function restart()
    {
        $pid = $this->getMasterPid();

        if ($this->isRunning($pid)) {
            $this->stop();
        }

        $this->start();
    }

    /**
     * 获取主进程PID
     */
    public function getMasterPid()
    {
        $pidFile = $this->config['pid_file'];

        if (is_file($pidFile)) {
            $masterPid = (int)file_get_contents($pidFile);
        } else {
            $masterPid = 0;
        }

        // $this->output->writeln('> Current Master PID:' . $masterPid);

        return $masterPid;
    }

    /**
     * 判断PID是否在运行
     *
     * @param  int $pid
     *
     * @return bool
     */
    public function isRunning($pid)
    {
        if (empty($pid)) {
            return false;
        }

        return Process::kill($pid, 0);
    }

    /**
     * 删除PID文件
     *
     * @access protected
     * @return void
     */
    public function removePid()
    {
        $masterPid = $this->config['pid_file'];

        if (is_file($masterPid)) {
            unlink($masterPid);
        }
    }

    // WebSocket服务启动时
    public function onWorkerStart()
    {
        // 声音提醒
        $this->remind();
    }

    // WebSocket客户端建立连接时，回调函数
    public function onOpen(\swoole_websocket_server $server, \swoole_http_request $request)
    {
    }

    /**
     * 接收并处理 WebSocket 客户端发来的消息
     *
     * @param \swoole_websocket_server $server
     * @param \swoole_websocket_frame $frame
     *
     * @return null
     */
    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame)
    {
        // 用户传送过来的数据
        $recMsg = json_decode($frame->data, true);

        // 客户端ID
        $clientID = $frame->fd;

        // 用户请求合法性判断
        if (!(is_array($recMsg) && isset($recMsg['Key']) && $recMsg['Key'] == 'Heartbeat')) {
            $this->toRemote($clientID, 'Error', 0, '无效的命令');

            return null;
        }
        // 根据token获取用户数据
        $token = $recMsg['Token'] ?? '';
        if (!$token) {
            return null;
        } else {
            $adminData = $token ? RedisUtil::getAdminToken($token) : [];
            $role      = $adminData['role'] ?? '';
            if ($role != 'super') {
                $this->toRemote($clientID, 'Error', 0, '无效的命令');

                return null;
            }

            // 缓存 Token 与 $clientID
            SysWsRedis::cacheTokenClient($token, $clientID);
        }
    }

    // WebSocket客户端连接关闭
    public function onClose($server, $fd)
    {
        // 获取客户端对应的token
        $token = SysWsRedis::getWsToken($fd);
        // 清除管理员
        SysWsRedis::removeToken($token, $fd);
    }

    /**
     * 向客户端发送统一格式的返回值
     *
     * @param int $clientID
     * @param string $key
     * @param string $code
     * @param string $msg
     * @param array $data
     */
    public function toRemote($clientID, $key, $code, $msg = '', $data = [])
    {
        $data = [
            'Key'  => $key,
            'code' => $code,
            'msg'  => $msg,
            'data' => $data,
        ];

        if ($this->server->connection_info($clientID)) {
            $this->server->push($clientID, json_encode($data));
        }
    }

    // 消息提醒
    public function remind()
    {
        try {
            swoole_timer_tick(1000, function () {
                $tokenList = SysWsRedis::getTokenList();

                foreach ($tokenList as $item) {
                    $value = unserialize($item);
                    $data  = SysWsRedis::getPromptFlag();
                    $this->toRemote($value[1], 'Remind', 1, '', $data);
                }
            });
        } catch (\Exception $e) {
            $this->output->writeln($e->getFile());
            $this->output->writeln($e->getLine());
            $this->output->writeln($e->getMessage());
            $this->output->writeln($e->getTraceAsString());
        }
    }

}
