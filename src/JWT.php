<?php

namespace GaeUtil;

use google\appengine\api\users\UserService;

/**
 * Description of JWT
 *
 * @author michael
 */
class JWT {

    /**
     * Project-to-project secret.
     * Internal do not expose.
     */
    const CONF_INTERNAL_SECRET_NAME = "jwt_internal_secret";

    /**
     * Frontend Secret.
     * Used to communicate frontend to backend.
     */
    const CONF_EXTERNAL_SECRET_NAME = "jwt_external_secret";

    /**
     * Persistent secret token
     */
    const CONF_SCOPED_SECRET_NAME = "jwt_scoped_secret";

    const ALG = "HS256";

    /**
     * Returns a valid JWT token for this account
     */
    static public function get($email = null) {
        static $cache;
        if (is_null($email)) {
            /**
             * Creating a token with the current module identity for reference.
             */
            $email = Util::get_current_module() . "@" . Util::get_current_application();
        }
        if (is_null($cache[$email])) {
            $payload = [
                "exp" => time() + 3.154e+7,
                "sub" => $email
            ];
            $cache[$email] = \Firebase\JWT\JWT::encode($payload, self::getSecret(), self::ALG);
        }
        return $cache[$email];

    }

    /**
     * Get Token used for service to service communication.
     * Setting standard time to 5 seconds for internal service to service communication.
     *
     * @return string
     * @throws \Exception
     */
    static public function getInternalToken() {
        static $token;
        if (is_null($token)) {
            $payload = [
                "exp" => time() + Moment::ONEHOUR,
                "sub" => Util::get_current_module() . "@" . Util::get_current_application()
            ];
            $secret = self::getSecret(self::CONF_INTERNAL_SECRET_NAME);
            $token = \Firebase\JWT\JWT::encode($payload, $secret, self::ALG);
        }
        return $token;
    }

    /**
     * @return string
     * @throws \Exception
     */
    static public function getExternalToken($current_user_email, $ttl = null) {
        static $token;
        if (is_null($token)) {
            if (is_null($ttl)) {
                $ttl = Moment::ONEHOUR;
            }
            $payload = [
                "exp" => time() + $ttl,
                "sub" => $current_user_email
            ];
            $secret = self::getSecret(self::CONF_EXTERNAL_SECRET_NAME);
            $token = \Firebase\JWT\JWT::encode($payload, $secret, self::ALG);
        }
        return $token;
    }

    static public function getScopedToken($scope) {
        static $token;
        if (is_null($token)) {
            $payload = [
                "exp" => time() + Moment::ONEHOUR,
                "sub" => Util::get_current_module() . "@" . Util::get_current_application()
            ];
            $secret = self::getSecret(self::CONF_SCOPED_SECRET_NAME);
            $token = \Firebase\JWT\JWT::encode($payload, $secret, self::ALG);
        }
        return $token;

    }

    static public function check($jwt_token) {
        $decoded = \Firebase\JWT\JWT::decode($jwt_token, self::getSecret(), [self::ALG]);
        return $decoded;
    }

    static public function getSecret($type = "jwt_external_secret") {
        $jwt_secret = Conf::get($type);
        if (is_null($jwt_secret)) {
            throw new \Exception("Trying to use JWT functions without a secret. This has to be set.");
        }
        return base64_decode($jwt_secret);
    }

    public static function get_internal_secret() {
        return self::getSecret(self::CONF_INTERNAL_SECRET_NAME);
    }

    public static function get_external_secret() {
        return self::getSecret(self::CONF_EXTERNAL_SECRET_NAME);
    }

    public static function get_scoped_secret() {
        return self::getSecret(self::CONF_SCOPED_SECRET_NAME);
    }

    public static function acceptJWTTokenInUrl() {
        $token = (isset($_GET['token']) && !empty($_GET['token'])) ? trim($_GET['token']) : false;
        if ($token) {
            $_SERVER["HTTP_AUTHORIZATION"] = "Bearer " . $token;
        }
    }

    static public function getSecureUrl($path, $query_data = []) {
        $query_data["token"] = self::getTokenForCurrentUser();
        return Util::get_full_path($path, $query_data);
    }

    static public function getTokenForCurrentUser() {
        $user = UserService::getCurrentUser();
        return self::get($user->getEmail());
    }

    static function generate_secret() {
        $random_pseudo_bytes = openssl_random_pseudo_bytes(32);
        return base64_encode($random_pseudo_bytes);
    }

}
