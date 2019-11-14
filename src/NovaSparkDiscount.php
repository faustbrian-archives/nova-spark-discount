<?php

namespace Artisanry\NovaSparkDiscount;

use Laravel\Nova\ResourceTool;

class NovaSparkDiscount extends ResourceTool
{
    /**
     * Get the displayable name of the resource tool.
     *
     * @return string
     */
    public function name()
    {
        return 'Nova Spark Discount';
    }

    /**
     * Get the component name for the resource tool.
     *
     * @return string
     */
    public function component()
    {
        return 'nova-spark-discount';
    }
}
