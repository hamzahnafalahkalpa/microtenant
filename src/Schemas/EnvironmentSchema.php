<?php

namespace Zahzah\MicroTenant\Schemas;

use Zahzah\LaravelSupport\Concerns;
use Zahzah\ModuleVersion\Schemas\EnvironmentSchema as SchemasEnvironmentSchema;

class EnvironmentSchema extends SchemasEnvironmentSchema{
    /**
     * Add feature version to model based on given name.
     *
     * @param  mixed  $model
     * @param  array|string  $names
     * @return void
     */
    protected function addModelHasFeature($model,array|string $names): void{
        $names   = $this->mustArray($names);
        $results = [];
        foreach ($names as $name) {
            $featureVersion = $this->MovelVersionModel()->where('name',$name)->first();
            $results[]      = [
                'master_feature_id'  => $featureVersion->master_feature_id,
                'feature_version_id' => $featureVersion->id
            ];
        }

        $model->modelHasFeatures()->createMany($results);
    }
}