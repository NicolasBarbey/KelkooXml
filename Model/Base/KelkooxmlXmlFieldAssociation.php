<?php

namespace KelkooXml\Model\Base;

use \Exception;
use \PDO;
use KelkooXml\Model\KelkooxmlXmlFieldAssociationQuery as ChildKelkooxmlXmlFieldAssociationQuery;
use KelkooXml\Model\Map\KelkooxmlXmlFieldAssociationTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Thelia\Model\AttributeQuery;
use Thelia\Model\Attribute as ChildAttribute;
use Thelia\Model\Feature as ChildFeature;
use Thelia\Model\FeatureQuery;

abstract class KelkooxmlXmlFieldAssociation implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\KelkooXml\\Model\\Map\\KelkooxmlXmlFieldAssociationTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the id field.
     * @var        int
     */
    protected $id;

    /**
     * The value for the xml_field field.
     * @var        string
     */
    protected $xml_field;

    /**
     * The value for the association_type field.
     * @var        int
     */
    protected $association_type;

    /**
     * The value for the fixed_value field.
     * @var        string
     */
    protected $fixed_value;

    /**
     * The value for the id_related_attribute field.
     * @var        int
     */
    protected $id_related_attribute;

    /**
     * The value for the id_related_feature field.
     * @var        int
     */
    protected $id_related_feature;

    /**
     * @var        Attribute
     */
    protected $aAttribute;

    /**
     * @var        Feature
     */
    protected $aFeature;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    /**
     * Initializes internal state of KelkooXml\Model\Base\KelkooxmlXmlFieldAssociation object.
     */
    public function __construct()
    {
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !!$this->modifiedColumns;
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return $this->modifiedColumns && isset($this->modifiedColumns[$col]);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return $this->modifiedColumns ? array_keys($this->modifiedColumns) : [];
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return boolean true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (Boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (Boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            if (isset($this->modifiedColumns[$col])) {
                unset($this->modifiedColumns[$col]);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>KelkooxmlXmlFieldAssociation</code> instance.  If
     * <code>obj</code> is an instance of <code>KelkooxmlXmlFieldAssociation</code>, delegates to
     * <code>equals(KelkooxmlXmlFieldAssociation)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param  mixed   $obj The object to compare to.
     * @return boolean Whether equal to the object specified.
     */
    public function equals($obj)
    {
        $thisclazz = get_class($this);
        if (!is_object($obj) || !($obj instanceof $thisclazz)) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey()
            || null === $obj->getPrimaryKey())  {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        if (null !== $this->getPrimaryKey()) {
            return crc32(serialize($this->getPrimaryKey()));
        }

        return crc32(serialize(clone $this));
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param  string  $name The virtual column name
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param  string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return KelkooxmlXmlFieldAssociation The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     *
     * @return KelkooxmlXmlFieldAssociation The current object, for fluid interface
     */
    public function importFrom($parser, $data)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data), TableMap::TYPE_PHPNAME);

        return $this;
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        return array_keys(get_object_vars($this));
    }

    /**
     * Get the [id] column value.
     *
     * @return   int
     */
    public function getId()
    {

        return $this->id;
    }

    /**
     * Get the [xml_field] column value.
     *
     * @return   string
     */
    public function getXmlField()
    {

        return $this->xml_field;
    }

    /**
     * Get the [association_type] column value.
     *
     * @return   int
     */
    public function getAssociationType()
    {

        return $this->association_type;
    }

    /**
     * Get the [fixed_value] column value.
     *
     * @return   string
     */
    public function getFixedValue()
    {

        return $this->fixed_value;
    }

    /**
     * Get the [id_related_attribute] column value.
     *
     * @return   int
     */
    public function getIdRelatedAttribute()
    {

        return $this->id_related_attribute;
    }

    /**
     * Get the [id_related_feature] column value.
     *
     * @return   int
     */
    public function getIdRelatedFeature()
    {

        return $this->id_related_feature;
    }

    /**
     * Set the value of [id] column.
     *
     * @param      int $v new value
     * @return   \KelkooXml\Model\KelkooxmlXmlFieldAssociation The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[KelkooxmlXmlFieldAssociationTableMap::ID] = true;
        }


        return $this;
    } // setId()

    /**
     * Set the value of [xml_field] column.
     *
     * @param      string $v new value
     * @return   \KelkooXml\Model\KelkooxmlXmlFieldAssociation The current object (for fluent API support)
     */
    public function setXmlField($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->xml_field !== $v) {
            $this->xml_field = $v;
            $this->modifiedColumns[KelkooxmlXmlFieldAssociationTableMap::XML_FIELD] = true;
        }


        return $this;
    } // setXmlField()

    /**
     * Set the value of [association_type] column.
     *
     * @param      int $v new value
     * @return   \KelkooXml\Model\KelkooxmlXmlFieldAssociation The current object (for fluent API support)
     */
    public function setAssociationType($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->association_type !== $v) {
            $this->association_type = $v;
            $this->modifiedColumns[KelkooxmlXmlFieldAssociationTableMap::ASSOCIATION_TYPE] = true;
        }


        return $this;
    } // setAssociationType()

    /**
     * Set the value of [fixed_value] column.
     *
     * @param      string $v new value
     * @return   \KelkooXml\Model\KelkooxmlXmlFieldAssociation The current object (for fluent API support)
     */
    public function setFixedValue($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->fixed_value !== $v) {
            $this->fixed_value = $v;
            $this->modifiedColumns[KelkooxmlXmlFieldAssociationTableMap::FIXED_VALUE] = true;
        }


        return $this;
    } // setFixedValue()

    /**
     * Set the value of [id_related_attribute] column.
     *
     * @param      int $v new value
     * @return   \KelkooXml\Model\KelkooxmlXmlFieldAssociation The current object (for fluent API support)
     */
    public function setIdRelatedAttribute($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id_related_attribute !== $v) {
            $this->id_related_attribute = $v;
            $this->modifiedColumns[KelkooxmlXmlFieldAssociationTableMap::ID_RELATED_ATTRIBUTE] = true;
        }

        if ($this->aAttribute !== null && $this->aAttribute->getId() !== $v) {
            $this->aAttribute = null;
        }


        return $this;
    } // setIdRelatedAttribute()

    /**
     * Set the value of [id_related_feature] column.
     *
     * @param      int $v new value
     * @return   \KelkooXml\Model\KelkooxmlXmlFieldAssociation The current object (for fluent API support)
     */
    public function setIdRelatedFeature($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id_related_feature !== $v) {
            $this->id_related_feature = $v;
            $this->modifiedColumns[KelkooxmlXmlFieldAssociationTableMap::ID_RELATED_FEATURE] = true;
        }

        if ($this->aFeature !== null && $this->aFeature->getId() !== $v) {
            $this->aFeature = null;
        }


        return $this;
    } // setIdRelatedFeature()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {


            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : KelkooxmlXmlFieldAssociationTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : KelkooxmlXmlFieldAssociationTableMap::translateFieldName('XmlField', TableMap::TYPE_PHPNAME, $indexType)];
            $this->xml_field = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : KelkooxmlXmlFieldAssociationTableMap::translateFieldName('AssociationType', TableMap::TYPE_PHPNAME, $indexType)];
            $this->association_type = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : KelkooxmlXmlFieldAssociationTableMap::translateFieldName('FixedValue', TableMap::TYPE_PHPNAME, $indexType)];
            $this->fixed_value = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : KelkooxmlXmlFieldAssociationTableMap::translateFieldName('IdRelatedAttribute', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id_related_attribute = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : KelkooxmlXmlFieldAssociationTableMap::translateFieldName('IdRelatedFeature', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id_related_feature = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 6; // 6 = KelkooxmlXmlFieldAssociationTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException("Error populating \KelkooXml\Model\KelkooxmlXmlFieldAssociation object", 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
        if ($this->aAttribute !== null && $this->id_related_attribute !== $this->aAttribute->getId()) {
            $this->aAttribute = null;
        }
        if ($this->aFeature !== null && $this->id_related_feature !== $this->aFeature->getId()) {
            $this->aFeature = null;
        }
    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(KelkooxmlXmlFieldAssociationTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildKelkooxmlXmlFieldAssociationQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->aAttribute = null;
            $this->aFeature = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see KelkooxmlXmlFieldAssociation::setDeleted()
     * @see KelkooxmlXmlFieldAssociation::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(KelkooxmlXmlFieldAssociationTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        try {
            $deleteQuery = ChildKelkooxmlXmlFieldAssociationQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $con->commit();
                $this->setDeleted(true);
            } else {
                $con->commit();
            }
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(KelkooxmlXmlFieldAssociationTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        $isInsert = $this->isNew();
        try {
            $ret = $this->preSave($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
            } else {
                $ret = $ret && $this->preUpdate($con);
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                KelkooxmlXmlFieldAssociationTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }
            $con->commit();

            return $affectedRows;
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            // We call the save method on the following object(s) if they
            // were passed to this object by their corresponding set
            // method.  This object relates to these object(s) by a
            // foreign key reference.

            if ($this->aAttribute !== null) {
                if ($this->aAttribute->isModified() || $this->aAttribute->isNew()) {
                    $affectedRows += $this->aAttribute->save($con);
                }
                $this->setAttribute($this->aAttribute);
            }

            if ($this->aFeature !== null) {
                if ($this->aFeature->isModified() || $this->aFeature->isNew()) {
                    $affectedRows += $this->aFeature->save($con);
                }
                $this->setFeature($this->aFeature);
            }

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                } else {
                    $this->doUpdate($con);
                }
                $affectedRows += 1;
                $this->resetModified();
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[KelkooxmlXmlFieldAssociationTableMap::ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . KelkooxmlXmlFieldAssociationTableMap::ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(KelkooxmlXmlFieldAssociationTableMap::ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(KelkooxmlXmlFieldAssociationTableMap::XML_FIELD)) {
            $modifiedColumns[':p' . $index++]  = 'XML_FIELD';
        }
        if ($this->isColumnModified(KelkooxmlXmlFieldAssociationTableMap::ASSOCIATION_TYPE)) {
            $modifiedColumns[':p' . $index++]  = 'ASSOCIATION_TYPE';
        }
        if ($this->isColumnModified(KelkooxmlXmlFieldAssociationTableMap::FIXED_VALUE)) {
            $modifiedColumns[':p' . $index++]  = 'FIXED_VALUE';
        }
        if ($this->isColumnModified(KelkooxmlXmlFieldAssociationTableMap::ID_RELATED_ATTRIBUTE)) {
            $modifiedColumns[':p' . $index++]  = 'ID_RELATED_ATTRIBUTE';
        }
        if ($this->isColumnModified(KelkooxmlXmlFieldAssociationTableMap::ID_RELATED_FEATURE)) {
            $modifiedColumns[':p' . $index++]  = 'ID_RELATED_FEATURE';
        }

        $sql = sprintf(
            'INSERT INTO kelkooxml_xml_field_association (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'ID':
                        $stmt->bindValue($identifier, $this->id, PDO::PARAM_INT);
                        break;
                    case 'XML_FIELD':
                        $stmt->bindValue($identifier, $this->xml_field, PDO::PARAM_STR);
                        break;
                    case 'ASSOCIATION_TYPE':
                        $stmt->bindValue($identifier, $this->association_type, PDO::PARAM_INT);
                        break;
                    case 'FIXED_VALUE':
                        $stmt->bindValue($identifier, $this->fixed_value, PDO::PARAM_STR);
                        break;
                    case 'ID_RELATED_ATTRIBUTE':
                        $stmt->bindValue($identifier, $this->id_related_attribute, PDO::PARAM_INT);
                        break;
                    case 'ID_RELATED_FEATURE':
                        $stmt->bindValue($identifier, $this->id_related_feature, PDO::PARAM_INT);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = KelkooxmlXmlFieldAssociationTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getId();
                break;
            case 1:
                return $this->getXmlField();
                break;
            case 2:
                return $this->getAssociationType();
                break;
            case 3:
                return $this->getFixedValue();
                break;
            case 4:
                return $this->getIdRelatedAttribute();
                break;
            case 5:
                return $this->getIdRelatedFeature();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {
        if (isset($alreadyDumpedObjects['KelkooxmlXmlFieldAssociation'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['KelkooxmlXmlFieldAssociation'][$this->getPrimaryKey()] = true;
        $keys = KelkooxmlXmlFieldAssociationTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getId(),
            $keys[1] => $this->getXmlField(),
            $keys[2] => $this->getAssociationType(),
            $keys[3] => $this->getFixedValue(),
            $keys[4] => $this->getIdRelatedAttribute(),
            $keys[5] => $this->getIdRelatedFeature(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->aAttribute) {
                $result['Attribute'] = $this->aAttribute->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
            if (null !== $this->aFeature) {
                $result['Feature'] = $this->aFeature->toArray($keyType, $includeLazyLoadColumns,  $alreadyDumpedObjects, true);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param      string $name
     * @param      mixed  $value field value
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return void
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = KelkooxmlXmlFieldAssociationTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @param      mixed $value field value
     * @return void
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setId($value);
                break;
            case 1:
                $this->setXmlField($value);
                break;
            case 2:
                $this->setAssociationType($value);
                break;
            case 3:
                $this->setFixedValue($value);
                break;
            case 4:
                $this->setIdRelatedAttribute($value);
                break;
            case 5:
                $this->setIdRelatedFeature($value);
                break;
        } // switch()
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = KelkooxmlXmlFieldAssociationTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) $this->setId($arr[$keys[0]]);
        if (array_key_exists($keys[1], $arr)) $this->setXmlField($arr[$keys[1]]);
        if (array_key_exists($keys[2], $arr)) $this->setAssociationType($arr[$keys[2]]);
        if (array_key_exists($keys[3], $arr)) $this->setFixedValue($arr[$keys[3]]);
        if (array_key_exists($keys[4], $arr)) $this->setIdRelatedAttribute($arr[$keys[4]]);
        if (array_key_exists($keys[5], $arr)) $this->setIdRelatedFeature($arr[$keys[5]]);
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(KelkooxmlXmlFieldAssociationTableMap::DATABASE_NAME);

        if ($this->isColumnModified(KelkooxmlXmlFieldAssociationTableMap::ID)) $criteria->add(KelkooxmlXmlFieldAssociationTableMap::ID, $this->id);
        if ($this->isColumnModified(KelkooxmlXmlFieldAssociationTableMap::XML_FIELD)) $criteria->add(KelkooxmlXmlFieldAssociationTableMap::XML_FIELD, $this->xml_field);
        if ($this->isColumnModified(KelkooxmlXmlFieldAssociationTableMap::ASSOCIATION_TYPE)) $criteria->add(KelkooxmlXmlFieldAssociationTableMap::ASSOCIATION_TYPE, $this->association_type);
        if ($this->isColumnModified(KelkooxmlXmlFieldAssociationTableMap::FIXED_VALUE)) $criteria->add(KelkooxmlXmlFieldAssociationTableMap::FIXED_VALUE, $this->fixed_value);
        if ($this->isColumnModified(KelkooxmlXmlFieldAssociationTableMap::ID_RELATED_ATTRIBUTE)) $criteria->add(KelkooxmlXmlFieldAssociationTableMap::ID_RELATED_ATTRIBUTE, $this->id_related_attribute);
        if ($this->isColumnModified(KelkooxmlXmlFieldAssociationTableMap::ID_RELATED_FEATURE)) $criteria->add(KelkooxmlXmlFieldAssociationTableMap::ID_RELATED_FEATURE, $this->id_related_feature);

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = new Criteria(KelkooxmlXmlFieldAssociationTableMap::DATABASE_NAME);
        $criteria->add(KelkooxmlXmlFieldAssociationTableMap::ID, $this->id);

        return $criteria;
    }

    /**
     * Returns the primary key for this object (row).
     * @return   int
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * Generic method to set the primary key (id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {

        return null === $this->getId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \KelkooXml\Model\KelkooxmlXmlFieldAssociation (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setXmlField($this->getXmlField());
        $copyObj->setAssociationType($this->getAssociationType());
        $copyObj->setFixedValue($this->getFixedValue());
        $copyObj->setIdRelatedAttribute($this->getIdRelatedAttribute());
        $copyObj->setIdRelatedFeature($this->getIdRelatedFeature());
        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return                 \KelkooXml\Model\KelkooxmlXmlFieldAssociation Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }

    /**
     * Declares an association between this object and a ChildAttribute object.
     *
     * @param                  ChildAttribute $v
     * @return                 \KelkooXml\Model\KelkooxmlXmlFieldAssociation The current object (for fluent API support)
     * @throws PropelException
     */
    public function setAttribute(ChildAttribute $v = null)
    {
        if ($v === null) {
            $this->setIdRelatedAttribute(NULL);
        } else {
            $this->setIdRelatedAttribute($v->getId());
        }

        $this->aAttribute = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildAttribute object, it will not be re-added.
        if ($v !== null) {
            $v->addKelkooxmlXmlFieldAssociation($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildAttribute object
     *
     * @param      ConnectionInterface $con Optional Connection object.
     * @return                 ChildAttribute The associated ChildAttribute object.
     * @throws PropelException
     */
    public function getAttribute(ConnectionInterface $con = null)
    {
        if ($this->aAttribute === null && ($this->id_related_attribute !== null)) {
            $this->aAttribute = AttributeQuery::create()->findPk($this->id_related_attribute, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aAttribute->addKelkooxmlXmlFieldAssociations($this);
             */
        }

        return $this->aAttribute;
    }

    /**
     * Declares an association between this object and a ChildFeature object.
     *
     * @param                  ChildFeature $v
     * @return                 \KelkooXml\Model\KelkooxmlXmlFieldAssociation The current object (for fluent API support)
     * @throws PropelException
     */
    public function setFeature(ChildFeature $v = null)
    {
        if ($v === null) {
            $this->setIdRelatedFeature(NULL);
        } else {
            $this->setIdRelatedFeature($v->getId());
        }

        $this->aFeature = $v;

        // Add binding for other direction of this n:n relationship.
        // If this object has already been added to the ChildFeature object, it will not be re-added.
        if ($v !== null) {
            $v->addKelkooxmlXmlFieldAssociation($this);
        }


        return $this;
    }


    /**
     * Get the associated ChildFeature object
     *
     * @param      ConnectionInterface $con Optional Connection object.
     * @return                 ChildFeature The associated ChildFeature object.
     * @throws PropelException
     */
    public function getFeature(ConnectionInterface $con = null)
    {
        if ($this->aFeature === null && ($this->id_related_feature !== null)) {
            $this->aFeature = FeatureQuery::create()->findPk($this->id_related_feature, $con);
            /* The following can be used additionally to
                guarantee the related object contains a reference
                to this object.  This level of coupling may, however, be
                undesirable since it could result in an only partially populated collection
                in the referenced object.
                $this->aFeature->addKelkooxmlXmlFieldAssociations($this);
             */
        }

        return $this->aFeature;
    }

    /**
     * Clears the current object and sets all attributes to their default values
     */
    public function clear()
    {
        $this->id = null;
        $this->xml_field = null;
        $this->association_type = null;
        $this->fixed_value = null;
        $this->id_related_attribute = null;
        $this->id_related_feature = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references to other model objects or collections of model objects.
     *
     * This method is a user-space workaround for PHP's inability to garbage collect
     * objects with circular references (even in PHP 5.3). This is currently necessary
     * when using Propel in certain daemon or large-volume/high-memory operations.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
        } // if ($deep)

        $this->aAttribute = null;
        $this->aFeature = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(KelkooxmlXmlFieldAssociationTableMap::DEFAULT_STRING_FORMAT);
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {

    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}