<?php
/*
 *  $Id: PlatformDefaultImpl.php,v 1.2 2005/02/03 04:18:54 hlellelid Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */
 
require_once 'propel/engine/platform/Platform.php';
include_once 'propel/engine/database/model/Domain.php';

/**
 * Default implementation for the Platform interface.
 *
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version $Revision: 1.2 $
 * @package propel.engine.platform
 */
class PlatformDefaultImpl implements Platform {

    private $schemaDomainMap;
    
    /**
     * Default constructor.
     */
    public function __construct() 
    {
        $this->initialize();
    }
    
    protected function initialize()
    {
        $this->schemaDomainMap = array();
        foreach(PropelTypes::getPropelTypes() as $type) {
            $this->schemaDomainMap[$type] = new Domain($type);
        }
		$this->schemaDomainMap[PropelTypes::BU_DATE] = new Domain("DATE");
		$this->schemaDomainMap[PropelTypes::BU_TIMESTAMP] = new Domain("TIMESTAMP");
        $this->schemaDomainMap[PropelTypes::BOOLEAN] = new Domain("INTEGER");
    }
    
    protected function setSchemaDomainMapping(Domain $domain) 
    {
        $this->schemaDomainMap[$domain->getType()] = $domain;
    }
    
    /**
     * @see Platform::getMaxColumnNameLength()
     */
    public function getMaxColumnNameLength()
    {
        return 64;
    }

    /**
     * @see Platform::getNativeIdMethod()
     */
    public function getNativeIdMethod()
    {
        return Platform::IDENTITY;
    }

    /**
     * @see Platform::getDomainForType()
     */
    public function getDomainForType($propelType) 
    {
        return $this->schemaDomainMap[$propelType];
    }

    /**
     * @return Only produces a SQL fragment if null values are
     * disallowed.
     * @see Platform::getNullString(boolean)
     */
    public function getNullString($notNull)
    {
        // TODO: Check whether this is true for all DBs.  Also verify
        // the old Sybase templates.
        return ($notNull ? "NOT NULL" : "");
    }

    /**
     * @see Platform::getAutoIncrement()
     */
    public function getAutoIncrement()
    {
        return "IDENTITY";
    }

    /**
     * @see Platform::hasScale(String)
     * TODO collect info for all platforms
     */
    public function hasScale($sqlType)
    {
        return true;
    }

    /**
     * @see Platform::hasSize(String)
     * TODO collect info for all platforms
     */
    public function hasSize($sqlType)
    {
        return true;
    }

    /**
     * @see Platform::escapeText()
     */ 
    public function escapeText($text)
    {
        return str_replace("'", "''", $text);
    }
    
    /**
     * @see Platform::supportsNativeDeleteTrigger()
     */
    public function supportsNativeDeleteTrigger()
    {
        return false;
    }
    
    /**
     * @see Platform::getBooleanString()
     */
    public function getBooleanString($b)
    {
        $b = ($b === true || strtolower($b) === 'true' || $b === 1 || $b === '1' || strtolower($b) === 'y' || strtolower($b) === 'yes');
        return ($b ? '1' : '0');
    }
}
