<?php

/**
 * This code was generated by
 * \ / _    _  _|   _  _
 * | (_)\/(_)(_|\/| |(/_  v1.0.0
 * /       /
 */

namespace Twilio\Rest\Studio\V1\Flow\Execution\ExecutionStep;

use Twilio\ListResource;
use Twilio\Version;

class ExecutionStepContextList extends ListResource {
    /**
     * Construct the ExecutionStepContextList
     *
     * @param Version $version Version that contains the resource
     * @param string $flowSid Flow Sid.
     * @param string $executionSid Execution Sid.
     * @param string $stepSid Step Sid.
     * @return \Twilio\Rest\Studio\V1\Flow\Execution\ExecutionStep\ExecutionStepContextList
     */
    public function __construct(Version $version, $flowSid, $executionSid, $stepSid) {
        parent::__construct($version);

        // Path Solution
        $this->solution = array(
            'flowSid' => $flowSid,
            'executionSid' => $executionSid,
            'stepSid' => $stepSid,
        );
    }

    /**
     * Constructs a ExecutionStepContextContext
     *
     * @return \Twilio\Rest\Studio\V1\Flow\Execution\ExecutionStep\ExecutionStepContextContext
     */
    public function getContext() {
        return new ExecutionStepContextContext(
            $this->version,
            $this->solution['flowSid'],
            $this->solution['executionSid'],
            $this->solution['stepSid']
        );
    }

    /**
     * Provide a friendly representation
     *
     * @return string Machine friendly representation
     */
    public function __toString() {
        return '[Twilio.Studio.V1.ExecutionStepContextList]';
    }
}