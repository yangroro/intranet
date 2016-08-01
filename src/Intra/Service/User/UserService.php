<?php
namespace Intra\Service\User;


use Intra\Core\MsgException;
use Intra\Model\UserModel;

class UserService
{

	/**
	 * @param $id
	 * @return UserDto
	 */

	public static function getDtobyId($id)
	{
		if (!UserModel::isExistById($id)) {
			return null;
		}
		$uid = UserModel::convertUidFromId($id);
		$dict = UserModel::getDictWithUid($uid);
		return UserDto::importFromDatabase($dict);
	}


	/**
	 * @param $uid
	 * @return UserDto
	 */
	public static function getDtobyUid($uid)
	{
		if (!UserModel::isExistByUid($uid)) {
			return null;
		}
		$dict = UserModel::getDictWithUid($uid);
		return UserDto::importFromDatabase($dict);
	}

	/**
	 * @param $id
	 * @throws MsgException
	 */
	public static function assertUserIdExist($id)
	{
		if (!UserModel::isExistById($id)) {
			throw new MsgException(
				'아이디가 없습니다. <a href="/users/join">가입</a>을 해주시거나 <a href="https://login.windows.net/common/oauth2/logout?response_type=code&client_id=***REMOVED***&resource=https://graph.windows.net&redirect_uri=">로그인 계정을 여러개 쓰는경우 로그인 해제</a>하고 다시 시도해주세요'
			);
		}

		$user_dto_object = UserInstanceService::importFromDatabaseWithId($id);

		if (!$user_dto_object->isValid()) {
			throw new MsgException(
				'로그인 불가능한 계정입니다. 인사팀에 확인해주세요. <a href="https://login.windows.net/common/oauth2/logout?response_type=code&client_id=***REMOVED***&resource=https://graph.windows.net&redirect_uri=">로그인 계정을 여러개 쓰는경우 로그인 해제</a>하고 다시 시도해주세요'
			);
		}
	}

	/**
	 * @return UserDto[]
	 */
	public static function getAvailableUserDtos()
	{
		$dicts = UserModel::getDictsAvailable();
		return UserDtoFactory::createFromDatabaseDicts($dicts);
	}


	/**
	 * @return UserDto[]
	 */
	public static function getAllUserDtos()
	{
		$dicts = UserModel::getAllDicts();
		return UserDtoFactory::createFromDatabaseDicts($dicts);
	}

	/**
	 * @return UserDto[]
	 */
	public static function getManagerUserDtos()
	{
		$return = [];
		$dicts = UserModel::getDictsOfManager();
		return UserDtoFactory::createFromDatabaseDicts($dicts);
	}

	/**
	 * @param $uids
	 * @return UserDto[]
	 */
	public static function getUserDtosByUid($uids)
	{
		$return = [];
		$dicts = UserModel::getDictWithUids($uids);
		foreach ($dicts as $dict) {
			$return[] = UserDto::importFromDatabase($dict);
		}
		return $return;
	}

	public static function join($request)
	{
		$join_dto = UserDto::importFromJoinRequest($request);
		self::assertJoin($join_dto);
		UserModel::addUser($join_dto);
	}

	private static function assertJoin($join_dto)
	{
		if (!preg_match("/^.+$/", $join_dto->name)) {
			throw new MsgException('이름을 입력해주세요');
		}
		if (!preg_match("/^.+@.+\..+$/", $join_dto->email)) {
			throw new MsgException('올바른 이메일을 입력해주세요');
		}
		if (!preg_match("/^[\w_\.]+$/", $join_dto->id)) {
			throw new MsgException('아이디는 영문과 숫자, 그리고 _(언더바)와 .(점)만 가능합니다');
		}
		if (!preg_match("/^.+$/", $join_dto->id)) {
			throw new MsgException('아이디를 입력해주세요');
		}
		if (!preg_match("/^\d+\/\d+\/\d+$/", $join_dto->birth) || $join_dto->birth == '0000/00/00') {
			throw new MsgException('생년월일을 올바르게 입력해주세요');
		}
		if (!preg_match("/^\d+-\d+-\d+$/", $join_dto->mobile) || $join_dto->mobile == '010-0000-0000') {
			throw new MsgException('전화번호를 올바르게 입력해주세요');
		}

		if (UserModel::isExistById($join_dto->id)) {
			throw new MsgException('이미 존재하는 계정입니다');
		}
	}

	public static function getNameByUidSafe($uid)
	{
		if (!UserModel::isExistByUid($uid)) {
			return null;
		}
		$dict = UserModel::getDictWithUid($uid);
		$dto = UserDto::importFromDatabase($dict);
		return $dto->name;
	}

	public static function getEmailByUidSafe($uid)
	{
		if (!UserModel::isExistByUid($uid)) {
			return null;
		}
		$dict = UserModel::getDictWithUid($uid);
		$dto = UserDto::importFromDatabase($dict);
		return $dto->email;
	}
}
