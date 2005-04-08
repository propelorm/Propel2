<?php
/*
 *  $Id: PlatformFactory.php,v 1.1 2004/07/08 00:22:57 hlellelid Exp $
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

include_once 'propel/engine/platform/PlatformDefaultImpl.php';

/**
 * Factory class responsible to create Platform objects that
 * define RDBMS platform specific behaviour.
 *
 * @author Hans Lellelid <hans@xmpl.org> (Propel)
 * @author Thomas Mahler (Torque)
 * @author Martin Poeschl <mpoeschl@marmot.at> (Torque)
 * @version $Revision: 1.1 $
 * @package propel.engine.platform
 */
class PlatformFactory {

    private static $platforms = array();

    /**
     * Returns the Platform for a platform name.
     *
     * @param dbms name of the platform
     */
    public static function getPlatformFor($dbms) {        
        $result = @self::$platforms[$dbms];        
        if ($result === null) {
            $cls = 'Platform' . ucfirst($dbms) . 'Impl';
            include_once 'propel/engine/platform/' . $cls . '.php';
            if (!class_exists($cls)) {
                throw new PropelException("Class $cls does not exist.");
            }
            $result = new $cls();            
            self::$platforms[$dbms] = $result;
        }
        return $result;
    }
    
}
