<?php
/*
 *  $Id: BasicValidator.php,v 1.2 2004/12/04 14:00:40 micha Exp $
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


/**
 * Basic Validator interface.
 *
 * BasicValidator objects perform validation without any knowledge of column/table
 * context.  They are simply given an input and some value and asked whether the input
 * is valid.
 *
 * @author Michael Aichler <aichler@mediacluster.de>
 * @version $Revision: 1.2 $
 * @package propel.validator
 */
class BasicValidator
{
  /**
  * Determine whether a value meets the criteria specified
  *
  * @param ValidatorMap $map
  * @param string $str a <code>String</code> to be tested
  *
  * @return mixed TRUE if valid, error message otherwise
  */
  function isValid(&$map, $str)
  {
    trigger_error(
      "Validator::isValid(): abstract function has to be reimplemented !",
      E_USER_ERROR
    );
  }
}