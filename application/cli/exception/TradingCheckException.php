<?php
namespace app\cli\exception;

/**
 * 交易检测异常没处理类
 * -- 用于委买、委卖、撤单等，各个交易环节，当检测条件不满足时抛出的异常信息
 *
 * @package app\cli\exception
 */
class TradingCheckException extends \Exception
{

}
