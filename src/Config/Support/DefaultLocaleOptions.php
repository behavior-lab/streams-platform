<?php namespace Anomaly\Streams\Platform\Config\Support;

use Anomaly\SelectFieldType\SelectFieldType;

/**
 * Class DefaultLocaleOptions
 *
 * @link   http://pyrocms.com/
 * @author PyroCMS, Inc. <support@pyrocms.com>
 * @author Ryan Thompson <ryan@pyrocms.com>
 */
class DefaultLocaleOptions
{

    /**
     * Handle the command.
     *
     * @param SelectFieldType $fieldType
     */
    public function handle(SelectFieldType $fieldType)
    {
        $fieldType->setOptions(
            array_combine(
                $keys = array_keys(config('streams::locales.supported')),
                array_map(
                    function ($locale) {
                        return trans('streams::locale.' . $locale . '.name') . ' (' . $locale . ')';
                    },
                    $keys
                )
            )
        );
    }
}
