<?php

/*
 *	$Id: ConcreteInheritanceBehaviorTest.php 1458 2010-01-13 16:09:51Z francois $
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

require_once 'tools/helpers/bookstore/BookstoreTestBase.php';

/**
 * Tests for ConcreteInheritanceParentBehavior class
 *
 * @author    François Zaniontto
 * @version   $Revision$
 * @package   generator.behavior.concrete_inheritance
 */
class ConcreteInheritanceParentBehaviorTest extends BookstoreTestBase
{
	public function testHasChildObject()
	{
		ConcreteArticleQuery::create()->deleteAll();
		ConcreteQuizzQuery::create()->deleteAll();
		ConcreteContentQuery::create()->deleteAll();
		$content = new ConcreteContent();
		$content->save();
		$this->assertFalse($content->hasChildObject());
		
		$article = new ConcreteArticle();
		$article->save();
		$content = $article->getConcreteContent();
		$this->assertTrue($content->hasChildObject());
	}
	
	public function testGetChildObject()
	{
		ConcreteArticleQuery::create()->deleteAll();
		ConcreteQuizzQuery::create()->deleteAll();
		ConcreteContentQuery::create()->deleteAll();
		$content = new ConcreteContent();
		$content->save();
		$this->assertNull($content->getChildObject());
		
		$article = new ConcreteArticle();
		$article->save();
		$content = $article->getConcreteContent();
		$this->assertEquals($article, $content->getChildObject());
	}
	
}
