<?php

class Tasks_calendar extends Tasks
{

	public function getDayNames()
	{
		foreach (array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat') as $day) {
			$days_of_week[] = array('day_name' => $day);
		}
		return $days_of_week;
	}

}