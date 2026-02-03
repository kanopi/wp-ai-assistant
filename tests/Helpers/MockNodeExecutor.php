<?php
/**
 * Helper class for mocking Node.js execution in tests
 */

namespace WP_AI_Tests\Helpers;

class MockNodeExecutor {
    /**
     * Mock responses for commands
     *
     * @var array
     */
    private static $mockResponses = [];

    /**
     * Captured commands
     *
     * @var array
     */
    private static $capturedCommands = [];

    /**
     * Set a mock response for a command pattern
     *
     * @param string $pattern Regex pattern to match command
     * @param string $output Output to return
     * @param int $exitCode Exit code to return
     * @return void
     */
    public static function setMockResponse($pattern, $output, $exitCode = 0) {
        self::$mockResponses[$pattern] = [
            'output' => $output,
            'exitCode' => $exitCode,
        ];
    }

    /**
     * Clear all mock responses
     *
     * @return void
     */
    public static function clearMockResponses() {
        self::$mockResponses = [];
        self::$capturedCommands = [];
    }

    /**
     * Get captured commands
     *
     * @return array
     */
    public static function getCapturedCommands() {
        return self::$capturedCommands;
    }

    /**
     * Get the last captured command
     *
     * @return string|null
     */
    public static function getLastCommand() {
        return end(self::$capturedCommands) ?: null;
    }

    /**
     * Mock exec function
     *
     * @param string $command Command to execute
     * @param array &$output Output lines
     * @param int &$returnVar Return value
     * @return string|false
     */
    public static function mockExec($command, &$output = null, &$returnVar = null) {
        self::$capturedCommands[] = $command;

        foreach (self::$mockResponses as $pattern => $response) {
            if (preg_match($pattern, $command)) {
                $output = explode("\n", $response['output']);
                $returnVar = $response['exitCode'];
                return end($output);
            }
        }

        // Default response if no pattern matches
        $output = [];
        $returnVar = 0;
        return '';
    }

    /**
     * Mock passthru function
     *
     * @param string $command Command to execute
     * @param int &$returnVar Return value
     * @return void
     */
    public static function mockPassthru($command, &$returnVar = null) {
        self::$capturedCommands[] = $command;

        foreach (self::$mockResponses as $pattern => $response) {
            if (preg_match($pattern, $command)) {
                echo $response['output'];
                $returnVar = $response['exitCode'];
                return;
            }
        }

        // Default response if no pattern matches
        $returnVar = 0;
    }

    /**
     * Create a success response for indexing
     *
     * @param int $processed Number of posts processed
     * @param int $chunks Number of chunks created
     * @return string
     */
    public static function createIndexSuccessResponse($processed = 10, $chunks = 25) {
        $response = [
            'success' => true,
            'stats' => [
                'totalPosts' => $processed,
                'processedPosts' => $processed,
                'totalChunks' => $chunks,
                'processedChunks' => $chunks,
                'errors' => 0,
            ],
            'errors' => [],
        ];

        return json_encode($response);
    }

    /**
     * Create an error response
     *
     * @param string $message Error message
     * @return string
     */
    public static function createErrorResponse($message) {
        $response = [
            'success' => false,
            'stats' => [
                'totalPosts' => 0,
                'processedPosts' => 0,
                'totalChunks' => 0,
                'processedChunks' => 0,
                'errors' => 1,
            ],
            'errors' => [
                ['message' => $message],
            ],
        ];

        return json_encode($response);
    }

    /**
     * Assert that a command was executed
     *
     * @param string $pattern Regex pattern to match command
     * @return bool
     */
    public static function assertCommandExecuted($pattern) {
        foreach (self::$capturedCommands as $command) {
            if (preg_match($pattern, $command)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Count how many times a command was executed
     *
     * @param string $pattern Regex pattern to match command
     * @return int
     */
    public static function countCommandExecutions($pattern) {
        $count = 0;

        foreach (self::$capturedCommands as $command) {
            if (preg_match($pattern, $command)) {
                $count++;
            }
        }

        return $count;
    }
}
