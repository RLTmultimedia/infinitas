<?php
class AssetsEventsTest extends CakeTestCase {
/**
 * @brief set up required objects for testing
 */
	public function startTest() {
		$this->Object = new Object();
		$this->Event = EventCore::getInstance();
	}

/**
 * @brief clear up the enviroment
 */
	public function endTest() {
		unset($this->Event, $this->Object);
		ClassRegistry::flush();
	}

/**
 * @brief test the instance is loaded correctly
 */
	public function testInstance() {
		$this->assertInstanceOf('EventCore', $this->Event);
	}

/**
 *@brief test required helpers load correctly
 */
	public function testRequireHelpers() {
		$this->Object->params = array();
		$expected = array('requireHelpersToLoad' => array('Assets' => array('Assets.Compress')));
		$result = $this->Event->trigger($this->Object, 'Assets.requireHelpersToLoad');
		$this->assertEquals($expected, $result);
	}

/**
 * @brief test required js loads correctly
 */
	public function testRequireJavascript() {
		$this->Object->params = array();
		$expected = array('requireJavascriptToLoad' => array('Assets' => array(
			'Assets.3rd/jquery',
			'Assets.3rd/jquery_ui',
			'Assets.3rd/metadata',
			'Assets.infinitas',
			'Assets.libs/core',
			'Assets.libs/form',
			'Assets.libs/html',
			'Assets.libs/number',
			'Assets.3rd/rater',
			'Assets.3rd/moving_boxes'
		)));
		$result = $this->Event->trigger($this->Object, 'Assets.requireJavascriptToLoad');
		$this->assertEquals($expected, $result);

		$this->Object->params = array(
			'admin' => true
		);
		$expected = array('requireJavascriptToLoad' => array('Assets' => array(
			'Assets.3rd/jquery',
			'Assets.3rd/jquery_ui',
			'Assets.3rd/metadata',
			'Assets.infinitas',
			'Assets.libs/core',
			'Assets.libs/form',
			'Assets.libs/html',
			'Assets.libs/number',
			'Assets.3rd/date',
			'Assets.3rd/image_drop_down'
		)));
		$result = $this->Event->trigger($this->Object, 'Assets.requireJavascriptToLoad');
		$this->assertEquals($expected, $result);
	}
}