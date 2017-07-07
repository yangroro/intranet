<?php

namespace Intra\Service\Menu;

use Intra\Service\Auth\ExceptTaAuth;
use Intra\Service\Auth\OnlyHolidayEditable;
use Intra\Service\Auth\OnlyPressManager;
use Intra\Service\Auth\OnlyUserManager;
use Intra\Service\Auth\PublicAuth;
use Intra\Service\Support\SupportPolicy;
use Intra\Service\User\UserSession;
use Silex\Application;
use Twig_Environment;

class MenuService
{
    const RIDI_GUIDE_URL = 'https://ridicorp.sharepoint.com/intranet/SitePages/%EB%A6%AC%EB%94%94%20%EC%83%9D%ED%99%9C%20%EA%B0%80%EC%9D%B4%EB%93%9C.aspx';

    public static function addToSilexTwig(Application $app)
    {
        list($left_menu_list, $right_menu_list) = self::getMenuLinkList();
        $app->extend(
            'twig',
            function (Twig_Environment $twig, $app) use ($left_menu_list, $right_menu_list) {
                $twig->addGlobal('leftMenuList', $left_menu_list);
                $twig->addGlobal('rightMenuList', $right_menu_list);

                return $twig;
            }
        );
    }

    /**
     * @return array
     */
    public static function getMenuLinkList(): array
    {
        if (UserSession::isLogined()) {
            if ($_ENV['domain'] == 'ridi.com') {
                $left_menu_list = [
                    new Link('직원찾기', '/users/', new PublicAuth()),
                    new Link('리디 생활 가이드', self::RIDI_GUIDE_URL, null, '_blank'),
                    new Link('전사 주간 업무 요약', '/weekly/', new ExceptTaAuth(), '_blank'),
                    new Link('회의실', '/rooms/', new PublicAuth()),
                    new Link('포커스룸', '/focus/'),
                    '근태관리' => [
                        new Link('휴가신청', '/holidays/', new PublicAuth()),
                        new Link('휴가조정(관리자)', '/holidayadmin/', new OnlyHolidayEditable()),
                        new Link('얼리파마', '/flextime/', new PublicAuth()),
                    ],
                    '지원요청' => [
                        new Link('업무환경 불편사항 문의', '/support/' . SupportPolicy::TYPE_DEVICE, new PublicAuth()),
                        new Link('경조 지원', '/support/' . SupportPolicy::TYPE_FAMILY_EVENT),
                        new Link('명함 신청', '/support/' . SupportPolicy::TYPE_BUSINESS_CARD),
                        new Link('구매 요청', '/support/' . SupportPolicy::TYPE_DEPOT, (new ExceptTaAuth())->accept(['hr.ta'])),
                        new Link('상품권 구매', '/support/' . SupportPolicy::TYPE_GIFT_CARD_PURCHASE),
                        new Link('저녁 주문', '/support/dinner', new PublicAuth(), '_blank'),
                        new Link('등기우편/퀵/해외배송', '/support/delivery', new PublicAuth(), '_blank'),
                        new Link('외부손님선물', '/support/present', new PublicAuth(), '_blank'),
                    ],
                    new Link('결제요청', '/payments/', (new ExceptTaAuth())->accept(['hr.ta', 'device.ta3'])),
                    new Link('비용정산', '/receipts/', new PublicAuth()),
                    new Link('급여관리', 'http://htms.himgt.net', new ExceptTaAuth(), '_blank'),
                    new Link('보도자료 관리', '/press/', new OnlyPressManager()),
                    new Link('조직도', '/organization/chart', new ExceptTaAuth(), '_blank'),
                ];
            } else {
                $left_menu_list = [
                    new Link('공지사항', '/posts/notice', new PublicAuth()),
                    new Link('휴가신청', '/holidays/', new PublicAuth()),
                    new Link('휴가조정(관리자)', '/holidayadmin/', new OnlyHolidayEditable()),
                    '지원요청' => [
                        new Link('업무환경 불편사항 문의', '/support/' . SupportPolicy::TYPE_DEVICE, new PublicAuth()),
                        new Link('경조 지원', '/support/' . SupportPolicy::TYPE_FAMILY_EVENT),
                        new Link('명함 신청', '/support/' . SupportPolicy::TYPE_BUSINESS_CARD),
                        new Link('구매 요청', '/support/' . SupportPolicy::TYPE_DEPOT, (new ExceptTaAuth())->accept(['hr.ta'])),
                        new Link('상품권 구매', '/support/' . SupportPolicy::TYPE_GIFT_CARD_PURCHASE),
                        new Link('저녁 주문', '/support/dinner', new PublicAuth(), '_blank'),
                        new Link('등기우편/퀵/해외배송', '/support/delivery', new PublicAuth(), '_blank'),
                        new Link('외부손님선물', '/support/present', new PublicAuth(), '_blank'),
                    ],
                    new Link('비용정산', '/receipts/', new PublicAuth()),
                    new Link('회의실', '/rooms/', new PublicAuth()),
                    new Link('포커스룸', '/focus/'),
                    new Link('리디 생활 가이드', '/users/'),
                    new Link('급여관리', 'http://htms.himgt.net', new ExceptTaAuth(), '_blank'),
                ];
            }

            $right_menu_list = [
                new Link('직원목록', '/users/list', new OnlyUserManager(), null, 'list'),
                new Link('내정보', '/users/myinfo', new PublicAuth(), null, 'cog'),
                new Link('로그아웃', '/usersession/logout', new PublicAuth(), null, 'log-out'),
            ];

            return [$left_menu_list, $right_menu_list];
        } else {
            $left_menu_list = [];
            $right_menu_list = [];

            return [$left_menu_list, $right_menu_list];
        }
    }
}
