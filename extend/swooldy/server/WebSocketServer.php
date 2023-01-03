<?php
namespace swooldy\server;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\console\input\Option;
use think\facade\Env;
use Swoole\Process;

/**
 * WebSocket服务器
 */
class WebSocketServer extends Command
{

    protected $host;
    protected $port;

    /** @var array 配置 */
    protected $config = [];

    /** @var \Swoole\WebSocket\Server */
    protected $server;

    /** @var array Server回调函数列表 */
    protected $callbacks = [];

    protected function configure()
    {
        $this->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload", 'start')
            ->addOption('daemon', 'd', Option::VALUE_NONE, 'Run the swoole server in daemon mode.')
            ->setDescription('WebSocket服务器');
    }

    protected function execute(Input $input, Output $output)
    {
        // 初始化服务器设置
        $this->init();

        // 执行服务起停动作
        $action = $input->getArgument('action');
        switch ($action) {
            case 'start':
                $this->start();
                break;
            case 'stop':
                $this->stop();
                break;
            case 'restart':
                $this->restart();
                break;
            case 'reload':
                $this->reload();
                break;
            default:
                $output->writeln("<error>Invalid argument action:{$action}, Expected start|stop|restart|reload .</error>");
        }
    }

    /**
     * 设置配置项
     *
     * @param array $configs
     */
    public function set($configs)
    {
        $this->config = array_merge($this->config, $configs);
    }

    // Server On Start
    public function onStart(\Swoole\Websocket\Server $server)
    {
    }

    // Server On WorkerStart
    public function onWorkerStart(\Swoole\Websocket\Server $server, int $workerID)
    {
    }

    // Server On Client Open
    public function onOpen(\Swoole\Websocket\Server $server, \Swoole\Http\Request $request)
    {
    }

    // Server On Client Message
    public function onMessage(\Swoole\Websocket\Server $server, \Swoole\Websocket\Frame $frame)
    {
    }

    // Server On Client Close
    public function onClose(\Swoole\WebSocket\Server $server, int $fd)
    {
    }

    /**
     * 输出异常Trace信息
     *
     * @param \Exception $e
     */
    public function writeTrace(\Exception $e)
    {
        $this->output->writeln($e->getFile());
        $this->output->writeln($e->getLine());
        $this->output->writeln($e->getMessage());
        $this->output->writeln($e->getTraceAsString());
    }

    /**
     * 初始化配置
     */
    protected function init()
    {
        $this->config['worker_num'] = 8;
        $this->config['daemonize']  = false;
        $this->config['dispatch_mode'] = 1;
        $this->config['buffer_output_size'] = 10485760;
        $this->config['socket_buffer_size'] = 10485760;
        $this->config['pid_file']   = Env::get('runtime_path') . "swoole_server_{$this->port}.pid";
        $this->config['log_file']   = Env::get('runtime_path') . "swoole_server_{$this->port}.log";
    }

    /**
     * 启动Server
     *
     * @return bool
     */
    protected function start()
    {
        // 判断是否已经运行
        $pid = $this->getMasterPid();
        if ($this->isRunning($pid)) {
            $this->output->writeln("<Error>websocket server process is already running. PID:{$pid}</Error>");

            return false;
        }

        // 是否以守护进程方式运行
        $this->config['daemonize'] = $this->input->hasOption('daemon');

        // 启动服务
        $this->server = new \Swoole\WebSocket\Server($this->host, $this->port);

        // 设置 server 运行前各项参数
        $this->server->set($this->config);

        // 注册回调函数
        $this->server->on('Start', [$this, 'onStart']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('Open', [$this, 'onOpen']);
        $this->server->on('Message', [$this, 'onMessage']);
        $this->server->on('Close', [$this, 'onClose']);

        $this->output->writeln("Websocket server Started: <http://{$this->host}:{$this->port}>");
        if ($this->config['daemonize'] == false) $this->output->writeln('You can exit with <info>`CTRL-C`</info>');

        // 启动WebSocket服务
        $this->server->Start();

        return true;
    }

    /**
     * 停止server
     */
    protected function stop()
    {
        $pid = $this->getMasterPid();

        if (!$this->isRunning($pid)) {
            $this->output->writeln('<Error>no websocket server process running.</Error>');

            return false;
        }

        $this->output->writeln('Stopping websocket server...');

        Process::kill($pid, SIGTERM);
        $this->removePid();

        $this->output->writeln('> success');
    }

    /**
     * 柔性重启server
     */
    protected function reload()
    {
        $pid = $this->getMasterPid();

        if (!$this->isRunning($pid)) {
            $this->output->writeln('<Error>no websocket server process running.</Error>');

            return false;
        }

        $this->output->writeln('Reloading websocket server...');
        Process::kill($pid, SIGUSR1);
        $this->output->writeln('> success');
    }

    /**
     * 重启server
     */
    protected function reStart()
    {
        $pid = $this->getMasterPid();

        if ($this->isRunning($pid)) {
            $this->stop();
        }

        $this->Start();
    }

    /**
     * 获取主进程PID
     */
    protected function getMasterPid()
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
    protected function isRunning($pid)
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
    protected function removePid()
    {
        $masterPid = $this->config['pid_file'];

        if (is_file($masterPid)) {
            unlink($masterPid);
        }
    }

}
