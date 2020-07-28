<?php

namespace Anomaly\Streams\Platform\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Anomaly\Streams\Platform\Support\Traits\Properties;
use Anomaly\Streams\Platform\Support\Traits\HasWorkflows;
use Anomaly\Streams\Platform\Support\Traits\FiresCallbacks;

/**
 * Class Workflow
 *
 * @link   http://pyrocms.com/
 * @author PyroCMS, Inc. <support@pyrocms.com>
 * @author Ryan Thompson <ryan@pyrocms.com>
 */
class Workflow
{

    use Properties;
    use HasWorkflows;
    use FiresCallbacks;

    /**
     * Process the workflow.
     *
     * @param array $payload
     * @return mixed
     */
    public function process(array $payload = [])
    {
        $this->triggerCallback('processing', $payload);

        foreach ($this->steps as $name => $step) {

            $this->triggerCallback('before_' . $name, $payload);

            $this->do($step, $payload);

            $this->triggerCallback('after_' . $name, $payload);
        }

        $this->fire('processed', $payload);
    }

    /**
     * Trigger the callbacks.
     *
     * @param [type] $name
     * @param array $payload
     */
    protected function triggerCallback($name, array $payload)
    {
        $callback = [
            'workflow' => $this->name ?: $this->name($this),
            'name' => $name,
        ];

        $payload = compact('payload', 'callback');

        $this->callback ? App::call($this->callback, $payload) : null;
    }

    /**
     * Add a step to the workflow.
     *
     * @param string $name
     * @param string|\Closure $step
     * @param integer $position
     * @return $this
     */
    public function add($name, $step = null, $position = null)
    {
        if (!$step && is_string($step)) {

            $step = $name;

            $name = $this->name($step);
        }

        if ($position === null) {
            $position = count($this->steps);
        }

        $this->steps = array_slice($this->steps, 0, $position, true) +
            [$name => $step] +
            array_slice($this->steps, $position, count($this->steps) - 1, true);

        return $this;
    }

    /**
     * Push a step to first.
     *
     * @param string $name
     * @param string|\Closure $step
     * @return $this
     */
    public function first($name, $step = null)
    {
        return $this->add($name, $step, 0);
    }

    /**
     * Add a step before another.
     *
     * @param string $target
     * @param string $name
     * @param string|\Closure $step
     * @return $this
     */
    public function before($target, $name, $step = null)
    {
        return $this->add($name, $step, array_search($target, array_keys($this->steps)));
    }

    /**
     * Add a step after another.
     *
     * @param string $target
     * @param string $name
     * @param string|\Closure $step
     * @return $this
     */
    public function after($target, $name, $step = null)
    {
        return $this->add($name, $step, array_search($target, array_keys($this->steps)) + 1);
    }

    /**
     * Add a step after another.
     *
     * @param string $name
     * @param string $name
     * @param string|\Closure $step
     * @return $this
     */
    public function set($name, $step)
    {
        $this->steps[$name] = $step;

        return $this;
    }

    /**
     * Name the steps.
     *
     * @param array $steps
     * @return array
     */
    private function named($steps)
    {
        $named = [];

        array_walk($steps, function ($step, $name) use (&$named) {

            if (is_string($name)) {

                $named[$name] = $step;

                return;
            }

            if (is_string($step)) {

                $named[$this->name($step)] = $step;

                return true;
            }

            if (is_object($step)) {

                $named[$this->name($step)] = $step;

                return true;
            }

            $named[$name] = $step;
        });

        return $named;
    }

    /**
     * Return the step name.
     *
     * @param mixed $step
     * @return string
     */
    private function name($step)
    {
        if (is_object($step)) {
            $step = get_class($step);
        }

        $step = explode('\\', $step);

        $step = end($step);

        return Str::snake($step);
    }

    /**
     * Do the step with the payload.
     *
     * @param mixed $step
     * @param array $payload
     */
    private function do($step, array $payload = [])
    {
        if (is_string($step)) {
            return App::call($step, $payload, 'handle');
        }

        if (is_callable($step)) {
            return App::call($step, $payload);
        }
    }

    /**
     * Get the steps.
     */
    public function getSteps()
    {
        return $this->steps;
    }

    /**
     * Set the steps.
     *
     * @param array $steps
     * @return $this
     */
    public function setSteps(array $steps)
    {
        $this->steps = $steps;

        return $this;
    }
}
