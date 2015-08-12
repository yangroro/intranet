<?php
/**
 * Created by PhpStorm.
 * User: ridi
 * Date: 13. 12. 24
 * Time: 오후 4:14
 */

namespace Intra\Service;

use DateTime;
use Intra\Model\UserHolidayModel;

class UserHolidayPolicy
{

	/**
	 * @var User
	 */
	private $user;
	private $user_holiday_model;

	public function __construct(User $user)
	{
		$db = IntraDb::getGnfDb();
		$this->db = $db;

		$this->user = $user;
		$this->user_holiday_model = new UserHolidayModel();
	}

	public function getRemainCost($yearly)
	{
		$full_cost = $this->getAvailableCost($yearly);
		$used_cost = $this->getUsedCost($yearly);
		return $full_cost - $used_cost;
	}

	/**
	 * @param $yearly
	 * @return int
	 */

	public function getAvailableCost($yearly)
	{
		$info = $this->getDetailInfomationByYearly($yearly);
		if ($yearly == 1) {
			//금년입사
			$preusable_cost = $info['preusable_cost'];
			$full_cost = $preusable_cost;
		} elseif ($yearly == 2) {
			//작년입사(회계기준 조금 적용)
			$usable_cost = $info['base_holiday_count'];
			$preusable_cost = $info['preusable_cost'];
			$preused_cost_prev_year = $info['preused_cost_prev_year'];
			$full_cost = $usable_cost + $preusable_cost - $preused_cost_prev_year;
		} elseif ($yearly == 3) {
			//작년입사(회계기준 조금 적용)
			$usable_cost = 15;
			$preused_cost_prev_year = $info['preused_cost_prev_year'];
			$full_cost = $usable_cost - $preused_cost_prev_year;
		} else {
			//2014년시작 기점으로 완전한 회계기준 적용
			$full_cost = min(25, 15 + floor(($yearly - 3) / 2));
		}
		$full_cost = $this->floorByZeroDotFive($full_cost);
		return $full_cost;
	}

	public function getDetailInfomationByYearly($yearly)
	{
		$ret = array();
		$on_date = $this->user->getOnDate();
		$yearly_begin_timestamp = $this->getYearlyBeginTimestamp($yearly);
		$yearly_end_timestamp = $this->getYearlyEndTimestamp($yearly);
		$ret['ondate'] = date('Y/m/d', strtotime($on_date));
		$ret['date_of_ondate'] = date('m/d', strtotime($on_date));
		if ($yearly == 1) {
			$from = date('Ymd', strtotime($on_date));
			$to = date('Ymd', min($yearly_end_timestamp, time()));
			$month_diff = floor(($to - $from) / 100);
			$ret['preusable_cost'] = max(0, $month_diff);
		}
		if ($yearly == 2) {
			//최대 12개아닌 11개까지 만 부여하기위해 '-1' 추가
			$next_year_of_ondate_timestamp = strtotime("+1 year", strtotime($on_date)) - 1;
			$end_of_yearmonth_range_timestamp = min($next_year_of_ondate_timestamp, time());

			$from = '00' . date('d', strtotime($on_date));
			$to = date('md', $end_of_yearmonth_range_timestamp);
			$month_diff = floor(($to - $from) / 100);
			$month_diff %= 12;
			$ret['preusable_cost'] = max(0, $month_diff);
		}
		if ($yearly == 2) {
			$beginOfPart = new DateTime($on_date);
			$endofPart = new DateTime();
			$endofPart->setTimestamp($yearly_begin_timestamp);

			$diff = $beginOfPart->diff($endofPart);
			$costByUpdatingYearRaw = 15 * ($diff->days - 1) / 365;
			$costByUpdatingYear = floor($costByUpdatingYearRaw * 10) / 10;
			$ret['base_holiday_count'] = $costByUpdatingYear;
			$ret['worked_day_last_year'] = $diff->days - 1;
		}
		if ($yearly == 2) {
			$ret['preused_cost_prev_year'] = floatval($this->getUsedCost($yearly - 1));
		}
		if ($yearly == 3) {
			$info = $this->getDetailInfomationByYearly(2);
			$last_year_usable_holiday_count = $this->getUsedCost(2) + $this->getUsedCost(1);
			$last_year_remain_cost = $info['base_holiday_count'];
			$exceeded_cost = $last_year_usable_holiday_count - $last_year_remain_cost;

			$ret['last_year_remain_cost'] = $last_year_remain_cost;
			$ret['last_year_usable_holiday_count'] = $last_year_usable_holiday_count;

			$ret['preused_cost_prev_year'] = max(0, $exceeded_cost);
		}
		return $ret;
	}

	public function getYearlyBeginTimestamp($yearly)
	{
		$onDate = $this->user->getOnDate();
		$yearly -= 1;
		$targetDate = strtotime("+{$yearly} year", strtotime($onDate));

		$year = date('Y', $targetDate);
		$yearlyBeginTimestamp = strtotime($year . '/1/1');
		return max(strtotime($onDate), $yearlyBeginTimestamp);
	}

	public function getYearlyEndTimestamp($yearly)
	{
		$onDate = $this->user->getOnDate();
		$yearly -= 1;
		$targetDate = strtotime("+{$yearly} year", strtotime($onDate));

		$year = date('Y', $targetDate);
		$yearlyEndTimestamp = strtotime($year . '/12/31');
		return $yearlyEndTimestamp;
	}

	/**
	 * @param $yearly
	 * @return int
	 */

	public function getUsedCost($yearly)
	{
		$begin = date('Y/m/d', $this->getYearlyBeginTimestamp($yearly));
		$end = date('Y/m/d', $this->getYearlyEndTimestamp($yearly));
		return $this->user_holiday_model->getUsedCost($this->user, $begin, $end);
	}

	/**
	 * @param $fullCost
	 * @return float
	 */
	private function floorByZeroDotFive($fullCost)
	{
		$fullCost = floor($fullCost * 2) / 2;
		return $fullCost;
	}
}
