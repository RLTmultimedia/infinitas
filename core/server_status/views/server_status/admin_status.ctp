<?php
	echo $this->element('current', array('current' => $current, 'plugin' => 'server_status'));
	echo $this->element('all_time', array('allTime' => $allTime, 'plugin' => 'server_status'));
	echo $this->element('last_two_weeks', array('lastTwoWeeks' => $lastTwoWeeks, 'plugin' => 'server_status'));
	echo $this->element('last_six_months', array('lastSixMonths' => $lastSixMonths, 'plugin' => 'server_status'));
	echo $this->element('by_hour', array('byHour' => $byHour, 'plugin' => 'server_status'));
	echo $this->element('by_day', array('byDay' => $byDay, 'plugin' => 'server_status'));