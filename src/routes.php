<?php
use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use InstagramAPI\Instagram;

\InstagramAPI\Instagram::$allowDangerousWebUsageAtMyOwnRisk = true;

return function (App $app) {
    $container = $app->getContainer();

    $app->post('/close-friends', function (Request $request, Response $response, array $args) use ($container) {
        $username = $request->getParam('username');
        $password = $request->getParam('password');

        try {
            $ig = new \InstagramAPI\Instagram();
            $ig->login($username, $password);
            $closeFriends = json_decode($ig->people->getCloseFriends(), true);

            return $response->withJson(['friends' => $closeFriends['users']]);
        } catch (\Throwable $th) {
            return $response->withJson(['error' => $th], 400);
        }
    });

    $app->post('/close-friends/add', function (Request $request, Response $response, array $args) use ($container) {
        $username  = $request->getParam('username');
        $password  = $request->getParam('password');
        $followers = $request->getParam('friends');

        try {
            $ig = new \InstagramAPI\Instagram();
            $ig->login($username, $password);

            $ig->people->setCloseFriends($followers, []);

            return $response->withJson(['message' => 'Followers have added to the close friends!']);
        } catch (\Throwable $th) {
            return $response->withJson(['error' => $th], 400);
        }
    });

    $app->post('/close-friends/remove', function (Request $request, Response $response, array $args) use ($container) {
        $username  = $request->getParam('username');
        $password  = $request->getParam('password');
        $followers = $request->getParam('friends');

        try {
            $ig = new \InstagramAPI\Instagram();
            $ig->login($username, $password);

            $ig->people->setCloseFriends([], $followers);

            return $response->withJson(['message' => 'Followers have removed from the close friends!']);
        } catch (\Throwable $th) {
            return $response->withJson(['error' => $th], 400);
        }
    });
};
