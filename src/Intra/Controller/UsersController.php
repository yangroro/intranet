<?php

namespace Intra\Controller;

use Intra\Service\User\UserConstant;
use Intra\Service\User\UserDto;
use Intra\Service\User\UserDtoFactory;
use Intra\Service\User\UserDtoHandler;
use Intra\Service\User\UserEditService;
use Intra\Service\User\UserJoinService;
use Intra\Service\User\UserMailService;
use Intra\Service\User\UserPolicy;
use Intra\Service\User\UserSession;
use Intra\Service\User\UserType;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UsersController implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $controller_collection = $app['controllers_factory'];
        $controller_collection->get('/', [$this, 'index']);
        $controller_collection->get('/list', [$this, 'getList']);
        $controller_collection->get('/myinfo', [$this, 'myInfo']);
        $controller_collection->post('/edit', [$this, 'edit']);
        $controller_collection->get('/image_upload', [$this, 'getUploadImage']);
        $controller_collection->get('/image_upload/{uid}', [$this, 'getUploadImage']);
        $controller_collection->post('/image_upload', [$this, 'uploadImage']);
        $controller_collection->post('/{userid}/updateExtra/{key}/{value}', [$this, 'updateExtraAjax']);
        $controller_collection->get('/jeditable_key/{key}', [$this, 'jeditableKey']);
        $controller_collection->get('/join', [$this, 'join']);
        $controller_collection->post('/join', [$this, 'joinAjax']);
        $controller_collection->get('/{uid}/image', [$this, 'image']);
        $controller_collection->get('/{uid}/thumb', [$this, 'thumb']);
        return $controller_collection;
    }

    public function index(Request $request, Application $app)
    {
        $self = UserSession::getSelfDto();
        $replaceable = UserPolicy::isFirstPageEditable($self);

        return $app['twig']->render('users/index.twig', [
            'replaceable' => $replaceable,
            'users' => UserDtoFactory::createAvailableUserDtos(),
            'allUsers' => UserDtoFactory::createAllUserDtos(),
            'allCurrentUsers' => UserDtoFactory::createAvailableUserDtos()
        ]);
    }

    public function getList(Request $request, Application $app)
    {
        if (!UserSession::isUserManager()) {
            return '권한이 없습니다';
        }

        $user_dtos = UserDtoFactory::createAllUserDtos();
        if ($request->get('outer')) {
            $user_dtos = array_filter($user_dtos, function (UserDto $item) {
                $type = (new UserDtoHandler($item))->getType();
                return $type == UserType::OUTER;
            });
        } else {
            $user_dtos = array_filter($user_dtos, function (UserDto $item) {
                $type = (new UserDtoHandler($item))->getType();
                return $type != UserType::OUTER;
            });
        }

        return $app['twig']->render('users/list.twig', [
            'users' => $user_dtos,
        ]);
    }

    public function myInfo(Request $request, Application $app)
    {
        $dto = UserSession::getSelfDto();
        return $app['twig']->render('users/myinfo.twig', ['info' => $dto]);
    }

    public function edit(Request $request, Application $app)
    {
        $uid = $request->get('uid');
        $key = $request->get('key');
        $value = $request->get('value');

        if (UserEditService::updateInfo($uid, $key, $value) !== null) {
            return Response::create($value, Response::HTTP_OK);
        } else {
            return Response::create("server error", Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    public function getUploadImage(Request $request, Application $app) {
        if (!UserSession::isUserManager()) {
            return '권한이 없습니다';
        }

        $uid = $request->get('uid');
        if (!$uid) {
            $uid = UserSession::getSelfDto()->uid;
        }
        $dto = UserDtoFactory::createByUid($uid);
        $users = UserDtoFactory::createAvailableUserDtos();

        return $app['twig']->render('users/image_upload.twig', [
            'uid' => $uid,
            'image' => $dto->image? $dto->image : null,
            'users' => $users,
        ]);
    }

    public function uploadImage(Request $request, Application $app)
    {
        $uploadedFile = $request->files->get('files')[0];
        $self = UserSession::getSelfDto();
        if (!$self) {
            return JsonResponse::create('unknown user', JsonResponse::HTTP_UNAUTHORIZED);
        }

        $uid = $self->uid;
        $savedFile = UserEditService::saveImage($uid, $uploadedFile);
        if ($savedFile != null) {
            $thumbFile = UserEditService::createThumb($uid, 180, 180);
            if ($thumbFile != null) {
                if (UserEditService::updateInfo($uid, 'image', '/users/' . $uid . '/image') != null) {
                    return JsonResponse::create('success');
                }
            }
        }

        return JsonResponse::create('file upload failed', JsonResponse::HTTP_SERVICE_UNAVAILABLE);
    }

    public function updateExtraAjax(Request $request, Application $app)
    {
        $uid = $request->get('userid');
        $key = $request->get('key');
        $value = rawurldecode($request->get('value'));

        $user = new UserDtoHandler(UserDtoFactory::createByUid($uid));
        $user->setExtra($key, $value);
        return Response::create('success', Response::HTTP_OK);
    }

    public function jeditableKey(Request $request, Application $app)
    {
        $key = $request->get('key');
        if (UserConstant::$jeditable_key_list[$key]) {
            $values = UserConstant::$jeditable_key_list[$key];
            $dicts = [];
            foreach ($values as $value) {
                $dicts[$value] = $value;
            }
            return JsonResponse::create($dicts);
        }
    }

    public function join(Request $request, Application $app)
    {
        return $app['twig']->render('users/join.twig', []);
    }

    public function joinAjax(Request $request, Application $app)
    {
        try {
            $new_user_dto = UserJoinService::join($request);
            if ($new_user_dto) {
                UserMailService::sendMail('인트라넷 회원가입', $new_user_dto, $app);
                return Response::create('success', Response::HTTP_OK);
            } else {
                return Response::create('fail', Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } catch (\Exception $e) {
            return Response::create($e->getMessage(), Response::HTTP_OK);
        }
    }

    public function image(Request $request, Application $app)
    {
        $uid = $request->get('uid');
        $file = UserEditService::getImageLocation($uid);
        if ($file !== null) {
            return BinaryFileResponse::create($file, Response::HTTP_OK);
        } else {
            return Response::create('file not exist', Response::HTTP_NOT_FOUND);
        }
    }

    public function thumb(Request $request, Application $app)
    {
        $uid = $request->get('uid');
        $file = UserEditService::getThumbLocation($uid);
        if ($file !== null) {
            return BinaryFileResponse::create($file, Response::HTTP_OK);
        } else {
            return Response::create('file not exist', Response::HTTP_NOT_FOUND);
        }
    }
}
