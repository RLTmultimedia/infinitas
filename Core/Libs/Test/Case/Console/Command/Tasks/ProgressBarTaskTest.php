<?php
	/**
	 * ProgressBarTask Test Cases
	 *
	 * Test Cases for progress bar shell task
	 *
	 * PHP version 5
	 *
	 * Copyright (c) 2010 Matt Curry
	 * www.PseudoCoder.com
	 * http://github.com/mcurry/progress_bar
	 *
	 * @author      Matt Curry <matt@pseudocoder.com>
	 * @license     MIT
	 *
	 */
	App::import('Shell', 'Shell', false);
	App::uses('ProgressBarTask', 'Libs.Console/Command/Task');

	if (!defined('DISABLE_AUTO_DISPATCH')) {
		define('DISABLE_AUTO_DISPATCH', true);
	}

	if (!class_exists('ShellDispatcher')) {
		ob_start();
		$argv = false;
		require CAKE . 'console' .  DS . 'cake.php';
		ob_end_clean();
	}

	/**
	 * TestProgressBarTask class
	 */
	class TestProgressBarTask extends ProgressBarTask {

		/**
		 * Output generated during test
		 *
		 * @var array
		 * @access public
		 */
		public $messages = array();

		/**
		 * niceRemaining proxy method
		 *
		 * @return void
		 * @access public
		 */
		public function niceRemaining() {
			return $this->_niceRemaining();
		}

		public function messages($clear = true) {
			$return = $this->messages;
			if ($clear) {
				$this->messages = array();
			}
			return $return;
		}

		public function out($message = NULL, $newlines = 1, $level = 1) {
			$this->messages[] = $message;
		}
	}

	/**
	 * ProgressBarTask Test class
	 */
	class ProgressBarTaskTest extends CakeTestCase {
/**
 * @brief set up at the start
 */
	public function setUp() {
		parent::setUp();
		$out = $this->getMock('ConsoleOutput', array(), array(), '', false);
		$in = $this->getMock('ConsoleInput', array(), array(), '', false);

		$this->Task = $this->getMock('TestProgressBarTask',
			array('in', 'err', 'createFile', '_stop', '_checkUnitTest'),
			array($out, $out, $in)
		);
		$this->Task->name = 'ProgressBar';
		$this->Task->path = TMP . 'tests' . DS;
	}

/**
 * @brief break down at the end
 */
	public function tearDown() {
		parent::tearDown();
		unset($this->Task);
	}

		/**
		 * testStartup method
		 *
		 * @return void
		 * @access public
		 */
		public function testStartup() {
			$total = 100;
			$now = time();
			$this->Task->start($total);
			$this->assertIdentical($this->Task->total, $total);
			$this->assertWithinMargin($this->Task->startTime, time(), 1);
			$this->assertIdentical($this->Task->done, 0);
		}

		/**
		 * testSimpleFormatting method
		 *
		 * @return void
		 * @access public
		 */
		public function testSimpleFormatting() {
			$this->Task->start(100);
			$this->Task->next(1);
			$result = $this->Task->messages();
			$result = end($result);
			$this->assertPattern('@\[>\s+\] 1.0% 1/100.*remaining$@', $result);

			$this->Task->next(49);
			$result = $this->Task->messages();
			$result = end($result);
			$this->assertPattern('@\[-+>\s+\] 50.0% 50/100.*remaining$@', $result);

			$this->Task->next(50);
			$result = $this->Task->messages();
			$result = end($result);
			$this->assertPattern('@\[-+>\] 100.0% 100/100.*remaining$@', $result);
		}

		/**
		 * testSimpleBoundaries method
		 *
		 * Test/demonstrate what happens when you bail early or overrun.
		 *
		 * @return void
		 * @access public
		 */
		public function testSimpleBoundaries() {
			// test changes based on terminal width so needs to be mocked
			$this->skipif (true);
			$this->Task->start(100);
			$this->Task->setTerminalWidth(80);
			$this->Task->size = 25;
			$this->Task->next(50);
			$this->Task->finish(1);

			$result = $this->Task->messages();
			$result = end($result);
			$this->assertPattern('@\[-{25}>] 50.0% 50/100.*remaining$@', $result);

			$this->Task->start(100);
			$this->Task->next(150);

			$result = $this->Task->messages();
			$result = end($result);
			$this->assertPattern('@\[-{12}>\] 150.0% 150/100.*remaining$@', $result);
		}

		/**
		 * testMessageUsage method
		 *
		 * @return void
		 * @access public
		 */
		public function testMessageUsage() {
			$this->Task->message('Running your 100 step process');
			$this->Task->start(100);
			$this->Task->terminalWidth = 100;

			$this->Task->next(1);
			$result = $this->Task->messages();
			$result = end($result);
			$this->assertPattern('@Running your 100 step process\s+1.0% 1/100.*remaining \[>\s+\]$@', $result);

			$this->Task->message('Changed and muuuuuuuuuuuuuuuuuch longer message');
			$this->Task->next(1);
			$result = $this->Task->messages();
			$result = end($result);
			$this->assertPattern('@Changed and muuuuuuuuuuuuuuuuuch longer message\s+2.0% 2/100.*remaining \[>\s+\]$@', $result);
		}

		/**
		 * testExecuteNothing method
		 *
		 * @return void
		 * @access public
		 */
		public function testExecuteNothing() {
			$this->assertNull($this->Task->execute());
		}

		/**
		 * testNext method
		 *
		 * @return void
		 * @access public
		 */
		public function testNext() {
			$this->Task->start(100);
			$this->Task->next();
			$this->assertIdentical($this->Task->done, 1);
		}

		/**
		 * testNiceRemainingUnknown method
		 *
		 * @return void
		 * @access public
		 */
		public function testNiceRemainingUnknown() {
			$this->Task->start(100);

			$expected = '?';
			$this->assertEqual($this->Task->niceRemaining(), $expected);

			$this->Task->next();
			$expected = '?';
			$this->assertEqual($this->Task->niceRemaining(), $expected);
		}

		/**
		 * testNiceRemainingBasic method
		 *
		 * @return void
		 * @access public
		 */
		public function testNiceRemainingBasic() {
			// 2 seconds per iteration, should take 20 seconds total.
			$total = 10;
			$delay = 2;
			$loops = 3;
			$this->Task->start($total);

			for ($i = 0; $i < $loops; $i++) {
				sleep($delay);
				$this->Task->next();
			}
			$result = $this->Task->niceRemaining();
			$expected = '14 secs';
			$this->assertEqual($result, $expected);

			// Testing numbers not necessarily nice and rounded
			// 2 seconds per iteration, should take 20 seconds total.
			$total = 9;
			$delay = 1;
			$loops = 4;
			$this->Task->start($total);

			for ($i = 0; $i < $loops; $i++) {
				sleep($delay);
				$this->Task->next();
			}
			$result = $this->Task->niceRemaining();
			$expected = '05 secs';
			$this->assertEqual($result, $expected);
		}

		/**
		 * testNiceRemainingMinutes method
		 *
		 * @return void
		 * @access public
		 */
		public function testNiceRemainingMinutes() {
			// 2 seconds per iteration, should take 120 seconds total.
			$total = 60;
			$delay = 2;
			$loops = 3;
			$this->Task->start($total);

			for ($i = 0; $i < $loops; $i++) {
				sleep($delay);
				$this->Task->next();
			}
			$result = $this->Task->niceRemaining();

			$expected = '1 min, 54 secs';
			$this->assertEqual($result, $expected);

			// 2 seconds per iteration, should take 200 seconds total.
			$total = 120;
			$delay = 2;
			$loops = 3;
			$this->Task->start($total);

			for ($i = 0; $i < $loops; $i++) {
				sleep($delay);
				$this->Task->next();
			}
			$result = $this->Task->niceRemaining();

			$expected = '3 mins, 54 secs';
			$this->assertEqual($result, $expected);
		}

		/**
		 * testSet method
		 *
		 * @return void
		 * @access public
		 */
		public function testSet() {
			$this->Task->start(100);
			$this->Task->set(50);
			$this->assertEqual($this->Task->done, 50);

			$this->Task->set(200);
			$this->assertEqual($this->Task->done, 100);
		}
	}
