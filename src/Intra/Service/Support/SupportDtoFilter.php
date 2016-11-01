<?php

namespace Intra\Service\Support;

class SupportDtoFilter
{

	/**
	 * @param SupportDto $support_dto
	 *
	 * @return SupportDto
	 */
	public static function filterAddingDto($support_dto)
	{
		$columns = SupportPolicy::getColumnFields($support_dto->target);
		if ($support_dto->target == SupportPolicy::TYPE_FAMILY_EVENT) {
			$category = $support_dto->dict[$columns['분류']->key];

			if ($category == '결혼') {
				$flower_type_column = '화환';
			} elseif (in_array($category, ['자녀출생', '졸업', '장기근속(3년)'])) {
				$flower_type_column = '과일바구니';
			} elseif (in_array($category, ['사망-부모 (배우자 부모 포함)', '사망-조부모 (배우자 조부모 포함)'])) {
				$flower_type_column = '조화';
			} else {
				$flower_type_column = '기타';
			}

			if ($support_dto->dict[$columns['화환 종류']->key] == '미선택시 자동선택') {
				$support_dto->dict[$columns['화환 종류']->key] = $flower_type_column;
			}

			if (in_array($category, [
				'결혼',
				'자녀출생',
				'사망-부모 (배우자 부모 포함)',
			])) {
				$cash = '1000000';
				$support_dto->dict[$columns['경조금']->key] = $cash;
			}
		} elseif ($support_dto->target == SupportPolicy::TYPE_BUSINESS_CARD) {
			if ($support_dto->dict[$columns['제작(예정)일']->key] == '') {
				$support_dto->dict[$columns['제작(예정)일']->key] = date("Y-m-t");
			}
		}
		$support_dto->dict['uuid'] = self::getUuidHeader($support_dto->target);
		return $support_dto;
	}

	private static function getUuidHeader($target)
	{
		$year = substr(date('Y'), 2, 2);
		$codes = [
			SupportPolicy::TYPE_BUSINESS_CARD => 'bs',
			SupportPolicy::TYPE_DEPOT => 'pe',
			SupportPolicy::TYPE_FAMILY_EVENT => 'bt',
			SupportPolicy::TYPE_GIFT_CARD => 'gt',
			SupportPolicy::TYPE_DEVICE => 'hp',
		];
		$code = $codes[$target];
		return "ridi-{$code}-{$year}";
	}
}