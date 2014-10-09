<?php

/**
 * Class class
 * This is a dummy class, to be used as a template whenever we need to create a new class
 *
 * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
 */
class ClassName extends DataClass {
    /**
     * Class ID
     * @var int
     */
    public $id                  = '';

    /**
     * Other Class ID
     * @var int
     */
    public $otherClass          = '';

    /**
     * Name
     * @var string
     */
    public $name                = '';

    /**
     * Created Date
     * @var string
     */
    public $created             = '';

    /**
     * Modified Date
     * @var string
     */
    public $modified            = '';

    /**
     * Active
     * @var boolean
     */
    public $active              = '';

    /**
     * Object table name
     * @access protected
     * @var string
     */
    protected $_sTable          = 'db_table_name';

    /**
     * Object table columns which names are different than the variable name
     * @access protected
     * @var string
     */
    protected $_aFields         = array('otherClass'     => 'other_class_id');

    /**
     * Object primary keys (respect the order on construction)
     * @access protected
     * @var array
     */
    protected $_aPrimaryKeys    = array('id');

    /**
     * Object primary keys pair checks
     * @access protected
     * @var array
     */
    protected $_aPrimariesChecks= array(array('fieldA', 'fieldB'), array('fieldA', 'fieldC'));

    /**
     * OtherClass Instance
     * @access private
     * @var OtherClass
     */
    private $otherClassObject;

    /**
     * Get OtherClass instance
     * @return OtherClass
     */
    public function getOtherClass() {
        if (!$this->otherClassObject) {
            $this->setOtherClass(new OtherClass($this->otherClass));
        }
        return $this->otherClassObject;
    }

    /**
     * Set OtherClass instance
     * @param OtherClass $otherClass
     */
    public function setOtherClass($otherClass)
    {
        if ($otherClass) {
            $this->otherClassObject = $otherClass;
        }
    }

    /**
     * Delete method overriding to avoid permanent deletes
     * @param boolean $permanentDelete
     */
    public function delete($permanentDelete = false)
    {
        Class_Manager::eraseClass($this);

        if ($permanentDelete) {
            parent::delete();
        }
    }


}

?>
