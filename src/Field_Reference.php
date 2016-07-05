<?php // vim:ts=4:sw=4:et:fdm=marker:fdl=0

namespace atk4\data;

class Field_Reference
{
    use \atk4\core\InitializerTrait {
        init as _init;
    }
    use \atk4\core\TrackableTrait;
    /**
     * What should we pass into owner->ref() to get
     * through to this reference
     */
    protected $link;

    /**
     * Definition of the destination model, that can
     * be either an object, a callback or a string.
     */
    protected $model;

    protected $our_field = null;

    /**
     * their field will be $table.'_id' by default.
     */
    protected $their_field = null;

    /**
     * default constructor. Will copy argument into properties
     */
    function __construct($defaults = [])
    {

        if (isset($defaults[0])) {
            $this->link = $defaults[0];
            unset($defaults[0]);
        }

        foreach ($defaults as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
     * Will use either foreign_alias or create #join_<table> 
     */
    public function getDesiredName()
    {
        return '#ref_'.$this->link;
    }

    public function init()
    {
        $this->_init();
        if (!$this->our_field) {
            $this->our_field = $this->link;
        }
        if (!$this->owner->hasElement($this->our_field)) {
            $this->owner->addField($this->our_field, ['system'=>true]);
        }
    }

    protected function getModel()
    {
        if (is_callable($this->model)) {
            $c = $this->model;
            return $c($this->owner, $this);
        }

        if (is_object($this->model)) {
            return $this->model;
        }

        throw new Exception([
            'Model is not defined for the relation',
            'model'=>$this->model
        ]);
    }

    protected function referenceOurValue()
    {
        $this->owner->persistence_data['use_table_prefixes']=true;
        return $this->owner->getElement($this->our_field);
    }

    /**
     * Adding field into join will automatically associate that field
     * with this join. That means it won't be loaded from $table but
     * form the join instead
     */
    public function ref()
    {

        $m = $this->getModel();
        if ($this->owner->loaded()) {
            if ($this->their_field) {
                return $m->loadBy($this->their_field, $this->owner[$this->our_field]);
            } else {
                return $m->load($this->owner[$this->our_field]);
            }

        } else {
            $m = clone $m; // we will be adding conditions!

            $values = $this->owner->action('fieldValues', [$this->our_field]);

            return $m->addCondition($this->their_field ?: $m->id_field, $values);
        }
    }

    /**
     * Creates model that can be used for generating sub-query acitons
     */
    public function refLink()
    {
        $m = $this->getModel();
        $m ->addCondition(
                $this->their_field ?: ($m->id_field),
                $this->referenceOurValue($m)
            );
    }
}