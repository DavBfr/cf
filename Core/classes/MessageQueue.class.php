<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2017 David PHAM-VAN
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/


class MqTimeoutException extends \Exception {
}


class MessageQueue {
	private $queue;
	private $queue_id;
	private $max_size = 2048;
	private $mode = 0600;


	/**
	 * MessageQueue constructor.
	 * @param int $queue_id
	 */
	public function __construct($queue_id = MESSAGE_QUEUE) {
		$this->queue_id = $queue_id;
		Logger::debug("Open MessageQueue #$queue_id");
		$this->queue = msg_get_queue($this->queue_id);
		// msg_stat_queue($this->queue);
	}


	/**
	 *
	 */
	public function delete() {
		msg_remove_queue($this->queue);
	}


	/**
	 * @param int $type Message type for filtering
	 * @param mixed $message json serializable message
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function send($type, $message) {
		$errorcode = null;
		if (!@msg_send($this->queue, $type, json_encode($message), false, false, $errorcode)) {
			throw new \Exception("Could not add message to queue ($errorcode)");
		}
	}


	/**
	 * @param int $type Message type for filtering
	 * @param int $timeout if > 0 raise exception if no this time arrive before a message
	 *                     return immediately if = 0, if -1: wait indefinitively
	 *
	 * @return array msg: array message, type: message type
	 * @throws MqTimeoutException
	 * @throws \Exception
	 */
	public function receive($type = 0, $timeout = 30) {
		$errorcode = null;
		$msg_type = null;
		$msg = null;
		$flags = 0;
		if ($timeout > 0) {
			pcntl_signal(SIGALRM, function () {
			}, false);
			pcntl_alarm($timeout);
		} elseif ($timeout == 0) {
			$flags = MSG_IPC_NOWAIT;
		}

		if (!msg_receive($this->queue, $type, $msg_type, $this->max_size, $msg, false, $flags, $errorcode)) {
			if ($errorcode == 4 || $errorcode == MSG_ENOMSG)
				throw new MqTimeoutException("MessageQueue::receive timeout");
			else
				throw new \Exception("Could not get message from queue ($errorcode)");
		}

		if ($timeout > 0)
			pcntl_alarm(0);

		Logger::debug("Message pulled from queue - type:{$msg_type}");
		return array("msg" => Input::jsonDecode($msg), "type" => $msg_type);
	}


	/**
	 * @param int $type Message type for filtering
	 * @param int $timeout if > 0 raise exception if no this time arrive before a message
	 *                     return immediately if = 0, if -1: wait indefinitively
	 *
	 * @throws \Exception
	 */
	public function dispatch($type = 0, $timeout = 30) {
		try {
			while (true) {
				$msg = $this->receive($type, $timeout);
				Plugins::dispatchAll("processMessage", $msg["type"], $msg["msg"]);
			}
		} catch (MqTimeoutException $e) {
		}
	}


	/**
	 *
	 */
	public static function process() {
		$queue_id = (int)Cli::addOption("queue", MESSAGE_QUEUE, "Message Queue ID (" . MESSAGE_QUEUE . ")");
		$timeout = (int)Cli::addOption("timeout", 0, "Time to wait");
		$delete = (int)Cli::addSwitch("delete", "Delete the queue when finished");
		Cli::enableHelp();

		try {
			$mq = new MessageQueue($queue_id);
			$mq->dispatch(0, $timeout);
			if ($delete) {
				$mq->delete();
			}
		} catch (\Exception $e) {
			Cli::pfatal($e->getMessage());
		}
	}

}
