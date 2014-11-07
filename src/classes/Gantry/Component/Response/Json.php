<?php
namespace Gantry\Component\Response;

class Json
{
    public $code = 200;
    public $success = true;
    public $data = null;
    public $messages = null;

    public function __construct($response = null, $success = true, array $messages = [])
    {
        // If messages exist add them to the output.
        $this->messages = $messages ?: null;

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

                if (DEBUG)
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
            while (DEBUG && $e);

            // Create response data on exceptions.
            $this->data = array('exceptions' => $exceptions);
        }
        else
        {
            $this->success = $success;
            $this->data = $response;
        }

        // Empty output buffer to make sure that the response is clean and valid.
        while (($output = ob_get_clean()) !== false)
        {
            // In debug mode send also output buffers (debug dumps, PHP notices and warnings).
            if ($output && defined(DEBUG)) {
                $this->messages['php'][] = $output;
            }
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string) json_encode($this);
    }
}
