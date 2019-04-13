<?php

namespace KanbanBoard;

use Utilities\Utilities;

class Authentication
{
    const STATE = 'LKHYgbn776tgubkjhk';

    private $client_id     = NULL;
    private $client_secret = NULL;

    /**
     * Authentication constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->client_id = Utilities::env('GH_CLIENT_ID');
        $this->client_secret = Utilities::env('GH_CLIENT_SECRET');
    }

    /**
     * @return string
     */
    public function getToken(): string
    {
        session_start();
        if (!Utilities::hasValue($_SESSION, 'gh-token'))
        {
            $this->login();
        }
        return $_SESSION['gh-token'];
    }


    /**
     * Login to GitHub and set github session token
     */
    public function login(): void
    {
        $token = NULL;
        if (Utilities::hasValue($_GET, 'code')
            && Utilities::hasValue($_GET, 'state')
            && $_SESSION['redirected'])
        {
            $_SESSION['redirected'] = false;
            $token = $this->_returnsFromGithub($_GET['code']);
        }
        else
        {
            $_SESSION['redirected'] = true;
            $this->_redirectToGithub();
        }
        $this->logout();
        $_SESSION['gh-token'] = $token;
    }

    public function logout(): void
    {
        unset($_SESSION['gh-token']);
    }

    private function _redirectToGithub(): void
    {
        $url = 'Location: https://github.com/login/oauth/authorize';
        $url .= '?client_id=' . $this->client_id;
        $url .= '&scope=repo';
        $url .= '&state=' . self::STATE;
        header($url);
        exit();
    }

    /**
     * @param string $code
     *
     * @return array
     */
    private function _returnsFromGithub(string $code): array
    {
        $url = 'https://github.com/login/oauth/access_token';
        $data = array(
            'code'          => $code,
            'state'         => self::STATE,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret);
        $options = array(
            'http' => array(
                'method'  => 'POST',
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($data),
            ),
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        if ($result === FALSE)
        {
            die('Error');
        }
        $result = explode('=', explode('&', $result)[0]);
        return array_slice($result, 2);
    }
}
