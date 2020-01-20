<?php
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use InstagramAPI\Instagram;

InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;

return function (App $app) {
    $container = $app->getContainer();

    $app->options('/{routes:.+}', function ($request, $response, $args) {
        return $response;
    });

    $app->add(function ($req, $res, $next) {
        $response = $next($req, $res);
        return $response
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
    });

    $app->post('/close-friends', function (Request $request, Response $response, array $args) use ($container) {
        $username = $request->getParam('username');
        $password = $request->getParam('password');

        try {
            $ig = new InstagramAPI\Instagram();
            $loginResponse = $ig->login($username, $password);

            if (!is_null($loginResponse) && $loginResponse->isTwoFactorRequired()) {
                $twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();

                 // The "STDIN" lets you paste the code via terminal for testing.
                 // You should replace this line with the logic you want.
                 // The verification code will be sent by Instagram via SMS.
                $verificationCode = trim(fgets(STDIN));
                $ig->finishTwoFactorLogin($verificationCode, $twoFactorIdentifier);
            }

            $closeFriends = json_decode($ig->people->getCloseFriends(), true);

            return $response->withJson(['friends' => $closeFriends['users']]);
        } catch (\Throwable $th) {
            return $response->withJson(['error' => $th], 400);
        }
    });

    $app->post('/close-friends/add', function (Request $request, Response $response, array $args) use ($container) {
        $username  = $request->getParam('username');
        $password  = $request->getParam('password');
        $member = $request->getParam('member');

        try {
            $ig = new InstagramAPI\Instagram();
            $ig->login($username, $password);
            $memberData = json_decode($ig->people->getInfoByName($member));
            $memberId = $memberData->user->pk;
            $memberFirstName = explode(' ', $memberData->user->full_name)[0];

            $ig->people->setCloseFriends([$memberId], []);
            $ig->direct->sendText(['users' => [$memberId]], "Olá {$memberFirstName}, agora você faz parte do meu close friends. Enjoy! :)");

            return $response->withJson(['status' => 'success', 'data' => $memberData ]);
        } catch (\Throwable $error) {
            return $response->withJson(['status' => 'failure', 'error' => $error->getMessage()], 400);
        }
    });

    $app->post('/close-friends/remove', function (Request $request, Response $response, array $args) use ($container) {
        $username  = $request->getParam('username');
        $password  = $request->getParam('password');
        $member = $request->getParam('member');

        try {
            $ig = new InstagramAPI\Instagram();
            $ig->login($username, $password);
            $memberData = json_decode($ig->people->getInfoByName($member));
            $memberId = $memberData->user->pk;

            $ig->people->setCloseFriends([], [$memberId]);

            return $response->withJson(['status' => 'success', 'message' => 'Follower has removed from the close friends!']);
        } catch (\Throwable $error) {
            return $response->withJson(['status' => 'failure', 'error' => $error->getMessage()], 400);
        }
    });

    $app->post('/comment', function (Request $request, Response $response, array $args) use ($container) {
        $ig          = new InstagramAPI\Instagram();
        $username    = $request->getParam('username');
        $password    = $request->getParam('password');
        $mediaId     = $request->getParam('mediaId');
        $commentText = $request->getParam('commentText');

        $ig->login($username, $password);

        $ig->media->comment($mediaId, $commentText);

        return $response->withJson(['status' => 'success', 'message' => 'Successful commented!']);
    });

    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function($req, $res) {
        $handler = $this->notFoundHandler; // handle using the default Slim page not found handler
        return $handler($req, $res);
    });
};
