<?php

namespace SleepingOwl\Admin\Traits;

trait ElementSaveRelationTrait
{
    /**
     * @var \Closure
     */
    protected $syncCallback;

    /**
     * @return array|string
     */
    public function getValueFromModel()
    {
        $value = parent::getValueFromModel();

        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $val;
            }
        }

        if ($value instanceof Collection && $value->count() > 0) {
            $value = $value->pluck($value->first()->getKeyName())->all();
        }

        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        return $value;
    }

    /**
     * @return \Closure
     */
    public function getSyncCallback()
    {
        return $this->syncCallback;
    }

    /**
     * @param \Closure $callable
     * @return $this
     */
    public function setSyncCallback(\Closure $callable)
    {
        $this->syncCallback = $callable;

        return $this;
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function save(\Illuminate\Http\Request $request)
    {
        if (is_null($this->getModelForOptions())) {
            parent::save($request);
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function afterSave(\Illuminate\Http\Request $request)
    {
        if (is_null($this->getModelForOptions())) {
            return;
        }

        if ($this->isValueSkipped()) {
            return;
        }

        $attribute = $this->getModelAttributeKey();

        if (is_null($request->input($this->getPath()))) {
            $values = [];
        } else {
            $values = $this->getValueFromModel();
        }

        $relation = $this->getModel()->{$attribute}();
        if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
            $this->syncBelongsToManyRelation($relation, $values);
        } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
            $this->deleteOldItemsFromHasManyRelation($relation, $values);
            $this->attachItemsToHasManyRelation($relation, $values);
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Relations\BelongsToMany $relation
     * @param array $values
     *
     * @return void
     */
    protected function syncBelongsToManyRelation(
        \Illuminate\Database\Eloquent\Relations\BelongsToMany $relation,
        array $values
    ) {
        foreach ($values as $i => $value) {
            if (! array_key_exists($value, $this->getOptions()) && $this->isTaggable()) {
                $model = clone $this->getModelForOptions();
                $model->{$this->getDisplay()} = $value;
                $model->save();

                $values[$i] = $model->getKey();
            }
        }

        if (is_callable($callback = $this->getSyncCallback())) {
            $callbackModel = $this->getModel();
            $callback($values, $callbackModel);

            return;
        }

        $relation->sync($values);
    }

    /**
     * @param \Illuminate\Database\Eloquent\Relations\HasMany $relation
     * @param array $values
     */
    protected function deleteOldItemsFromHasManyRelation(
        \Illuminate\Database\Eloquent\Relations\HasMany $relation,
        array $values
    ) {
        $items = $relation->get();

        foreach ($items as $item) {
            if (! in_array($item->getKey(), $values)) {
                if ($this->isDeleteRelatedItem()) {
                    $item->delete();
                } else {
                    $item->{$this->getForeignKeyNameFromRelation($relation)} = null;
                    $item->save();
                }
            }
        }
    }

    /**
     * @param \Illuminate\Database\Eloquent\Relations\HasMany $relation
     * @param array $values
     */
    protected function attachItemsToHasManyRelation(
        \Illuminate\Database\Eloquent\Relations\HasMany $relation,
        array $values
    ) {
        foreach ($values as $i => $value) {
            /** @var Model $model */
            $model = clone $this->getModelForOptions();
            $item = $model->find($value);

            if (is_null($item)) {
                if (! $this->isTaggable()) {
                    continue;
                }

                $model->{$this->getDisplay()} = $value;
                $item = $model;
            }

            $relation->save($item);
        }
    }
}
