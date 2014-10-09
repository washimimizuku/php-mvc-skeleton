<?php
/**
 * RabbitMQ.class.php
 *
 * Path: /lib/utils/RabbitMQ.class.php
 *
 * @author Pedro Carmo <p.carmo@ydigitalmedia.com>
 * @package utils
 */

/**
 * Main configurations class
 */
require_once(getenv('app_root').'/framework/lib/core/ApplicationConfig.class.php');
require_once(getenv('app_root').'/framework/lib/vendor/php-amqplib/vendor/autoload.php');

use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * RabbitMQ class
 *
 * @author Pedro Carmo <p.carmo@ydigitalmedia.com>
 */
class RabbitMQ
{
	private static $channel = null;
	private static $connection = null;
	private static $exchange = null;
	private static $queue = null;

	/**
	 * Instance the connection and channel for the rabbit server
	 *
	 * @param string $exchange Exchange name
	 * @param string $queue Exchange queue
	 */
	static function init($exchange, $queue)
	{
		$config = ApplicationConfig::getInstance();

		if (empty(self::$connection))
		{
			self::$connection = new AMQPConnection(
				$config->rabbitHost, $config->rabbitPort,
				$config->rabbitUser, $config->rabbitPass,
				$config->rabbitVhost
			);
		}

		if (empty(self::$channel))
		{
			self::$channel = self::$connection->channel();

			/*
				name: $queue
				passive: false
				durable: true // the queue will survive server restarts
				exclusive: false // the queue can be accessed in other channels
				auto_delete: false //the queue won't be deleted once the channel is closed.
			*/
			self::$channel->queue_declare($queue, false, true, false, false);

			/*
				name: $exchange
				type: direct
				passive: false
				durable: true // the exchange will survive server restarts
				auto_delete: false //the exchange won't be deleted once the channel is closed.
			*/
			self::$channel->exchange_declare($exchange, 'direct', false, true, false);

			self::$channel->queue_bind($queue, $exchange);

			self::$queue = $queue;
			self::$exchange = $exchange;
		}

	}

	/**
	 * Publish a message to the instanced exchange
	 *
	 * @param string $messageBody Content of the message to publish
	 * @param array $properties Extra properties on the message
	 */
	static function publish($messageBody, $properties = array())
	{
		if (empty(self::$channel))
		{
			throw new \Exception('You need to initialize the channel before any methods can be ran');
		}

		if (!isset($properties['delivery_mode']))
		{
			$properties['delivery_mode'] = 2;
		}

		$message = new AMQPMessage($messageBody, $properties);
		self::$channel->basic_publish($message, self::$exchange);
	}

	/**
	 * Start a consumer that returns a message to the $callback method
	 *
	 * @param string $callback Callback method the defined by the consumer script
	 * @param bool $acknowledge Mark message as acknowledged once you consume it (rather than doing it manually) -- use on very specific situations
	 * @param bool $exclusive Make this consumer exclusive -- ie, the queue is locked to this single consumer
	 * @param string $consumerTag
	 */
	static function consume($callback, $acknowledge = false, $exclusive = false, $consumerTag = "")
	{
		if (empty(self::$channel))
		{
			throw new \Exception('You need to initialize the channel before any methods can be ran');
		}

		/*
			queue: Queue from where to get the messages
			consumer_tag: Consumer identifier
			no_local: Don't receive messages published by this consumer.
			no_ack: Tells the server if the consumer will acknowledge the messages.
			exclusive: Request exclusive consumer access, meaning only this consumer can access the queue
			nowait:
			callback: A PHP Callback
		*/
		self::$channel->basic_consume(
			self::$queue,
			$consumerTag,
			false,
			$acknowledge,
			$exclusive,
			false,
			$callback);

		while (count(self::$channel->callbacks))
		{
			self::$channel->wait();
		}
	}

	/**
	 * Mark a message as consumed so it is not sent to other consumers
	 * This should be done after processing, to make sure the action you are trying to do is completed successfully
	 * Use this rather than set the consumer to do it automatically (unless you know what you're doing)
	 *
	 * @param AMQPMessage $message
	 */
	static function acknowledgeMessage(AMQPMessage $message)
	{
		$message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
	}

	/**
	 * Open need a new channel, closing the previous one
	 *
	 * @param $exchange
	 * @param $queue
	 */
	static function newChannel($exchange, $queue)
	{
		if (!empty(self::$channel))
		{
			self::$channel->close();
		}
		self::init($exchange, $queue);
	}

	/**
	 * Close the channel and connection
	 */
	static function shutdown()
	{
		if (!empty(self::$channel))
		{
			self::$channel->close();
			self::$channel = null;
		}

		if (!empty(self::$connection))
		{
			self::$connection->close();
			self::$connection = null;
		}
	}
}

?>
