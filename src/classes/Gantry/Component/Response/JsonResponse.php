<?php
namespace Gantry\Component\Response;

use Gantry\Framework\Base\Gantry;

class JsonResponse
{
    public $code = 200;
    public $success = true;

    protected $responseCodes = [
        200 => '200 OK',
        400 => '400 Bad Request',
        401 => '401 Unauthorized',
        403 => '403 Forbidden',
        404 => '404 Not Found',
        410 => '410 Gone',
        500 => '500 Internal Server Error',
        501 => '501 Not Implemented',
        503 => '503 Service Temporarily Unavailable'
    ];

    public function __construct($response = null, $success = true)
    {
        // Check if we are dealing with an error.
        if ($response instanceof \Exception)
        {
            // Prepare the error response
            $this->success = false;

            $this->code = $response->getCode();

            // Build data from exceptions.
            $exceptions = array();
            $e = $response;

            do
            {
                $exception = array(
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                );

                if (GANTRY_DEBUG)
                {
                    $exception += array(
                        'type' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine()
                    );
                }

                $exceptions[] = $exception;
                $e = $e->getPrevious();
            }
            while (GANTRY_DEBUG && $e);

            // Create response data on exceptions.
            $this->html = $this->renderError($response);
            $this->exceptions = $exceptions;
        }
        else
        {
            $this->success = $success;
            if (is_array($response)) {
                foreach ($response as $key => $value) {
                    $this->{$key} = $value;
                }
            } else {
                $this->data = $response;
            }
        }

        // Empty output buffer to make sure that the response is clean and valid.
        while (($output = ob_get_clean()) !== false)
        {
            // In debug mode send also output buffers (debug dumps, PHP notices and warnings).
            if ($output && defined(GANTRY_DEBUG)) {
                $this->messages['php'][] = $output;
            }
        }
    }

    protected function renderError(\Exception $exception) {
        try {
            $gantry = Gantry::instance();

            return $gantry['admin.theme']->render(
                '@gantry-admin/error.html.twig',
                ['title' => $this->getResponseStatus(), 'error' => $exception, 'ajax' => 1]
            );
        } catch (\Exception $e) {
            return "<h1>{$this->getResponseStatus()}</h1><p>{$exception->getMessage()}</p>";
        }
    }

    /**
     * @return int
     */
    public function getResponseCode() {
        return isset($this->responseCodes[$this->code]) ? (int) $this->code : 500;
    }

    /**
     * @return string
     */
    public function getResponseStatus() {
        return $this->responseCodes[$this->getResponseCode()];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) json_encode($this);
    }
}
