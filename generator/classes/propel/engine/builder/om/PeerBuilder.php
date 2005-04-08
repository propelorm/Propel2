<?php
/*
 *  $Id: PeerBuilder.php,v 1.1 2004/07/08 00:22:57 hlellelid Exp $
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

include_once 'propel/engine/database/model/Column.php';
 
 /**
  * Static class used to help encapsulate logic from the om-building templates.
  * 
  * @author Hans Lellelid <hans@xmpl.org>
  * @version $Revision: 1.1 $
  * @package propel.engine.builder.om
  */
class PeerBuilder {

    /**
     * Get the column constant name (e.g. PeerName::COLUMN_NAME).
     * 
     * @param Column $col The column we need a name for.
     * @param string $phpName The PHP Name of the peer class. The 'Peer' is appended automatically.
     * 
     * @return string If $phpName is provided, then will return {$phpName}Peer::COLUMN_NAME; if not, just COLUMN_NAME.
     */
    public static function getColumnName(Column $col, $phpName = null) {
        // was it overridden in schema.xml ?
        if ($col->getPeerName()) {
            $const = strtoupper($col->getPeerName());
        } else {
            $const = strtoupper($col->getName());
        }
        if ($phpName !== null) {
            return $phpName . 'Peer::' . $const;
        } else {
            return $const;
        }
    }

}