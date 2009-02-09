<?php
/*
 *  $Id$
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
 * Populates data needed by the bookstore-cms unit tests.
 *
 * @author     Hans Lellelid <hans@xmpl.org>
 */
class CmsDataPopulator {

	public static function populate()
	{
		$dbh = Propel::getConnection(PagePeer::DATABASE_NAME);
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 1,194,'home')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 2,5,'school')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 6,43,'education')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 44,45,'simulator')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 46,47,'ac')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 3,4,'history')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 7,14,'master-mariner')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 8,9,'education')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 48,85,'courses')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 98,101,'contact')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 10,11,'entrance')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 104,191,'intra')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 102,103,'services')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 12,13,'competency')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 15,22,'watchkeeping-officer')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 16,17,'education')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 18,19,'entrance')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 20,21,'competency')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 31,38,'watchkeeping-engineer')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 32,33,'education')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 34,35,'entrance')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 36,37,'competency')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 39,40,'practice')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 86,97,'news')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 95,96,'2007-02')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 99,100,'personnel')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 87,88,'2007-06')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 49,50,'nautical')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 51,52,'radiotechnical')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 53,54,'resourcemgmt')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 57,58,'safety')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 59,60,'firstaid')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 61,62,'sar')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 67,84,'upcoming')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 65,66,'languages')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 55,56,'cargomgmt')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 119,120,'timetable')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 63,64,'boaters')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 105,118,'bulletinboard')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 106,107,'sdf')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 41,42,'fristaende')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 23,30,'ingenj')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 24,25,'utbildn')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 26,27,'ansokn')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 93,94,'utexaminerade')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 89,92,'Massan')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 192,193,'lankar')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 68,69,'FRB')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 70,71,'pelastautumis')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 72,73,'CCM')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 74,75,'sjukvard')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 121,188,'Veckoscheman')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 134,135,'VS3VSVsjukv')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 122,123,'sjoarb')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 130,131,'fysik1')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 140,141,'kemi')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 76,77,'inr')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 78,79,'forare')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 144,145,'AlexandraYH2')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 132,133,'AlexandraVS2')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 80,81,'Maskin')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 126,127,'forstahjalp')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 136,137,'Juridik')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 142,143,'mate')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 82,83,'basic')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 124,125,'mask')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 108,109,'magnus')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 138,139,'sjosakerhet')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 28,29,'pate')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 148,149,'eng')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 146,147,'forstahjalpYH1')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 110,111,'kortoverlevnadskurs')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 158,159,'kortoverlevnadskurs')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 128,129,'metall')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 152,153,'fysik')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 156,157,'fardplan')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 154,155,'astro')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 90,91,'utstallare')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 150,151,'eng')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 160,161,'ent')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 162,163,'juridik')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 168,169,'svenska')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 164,165,'matemat')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 166,167,'operativa')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 170,171,'plan')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 172,173,'src')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 112,113,'sjukv')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 174,175,'matemati')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 176,177,'fysiikka')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 114,115,'hantv')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 116,117,'CCM')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 178,179,'haveri')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 180,181,'FRB')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 182,183,'kemia')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 184,185,'vaktrutiner')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 189,190,'laroplan')");
		$dbh->exec("INSERT INTO Page (ScopeId, LeftChild, RightChild, Title) VALUES (1, 186,187,'SSOkurs')");

		$dbh->exec("INSERT INTO Category (LeftChild, RightChild, Title) VALUES (1, 8, 'Cat_1')");
		$dbh->exec("INSERT INTO Category (LeftChild, RightChild, Title) VALUES (2, 7, 'Cat_1_1')");
		$dbh->exec("INSERT INTO Category (LeftChild, RightChild, Title) VALUES (3, 6, 'Cat_1_1_1')");
		$dbh->exec("INSERT INTO Category (LeftChild, RightChild, Title) VALUES (4, 5, 'Cat_1_1_1_1')");
	}

	public static function depopulate()
	{
		$dbh = Propel::getConnection(PagePeer::DATABASE_NAME);
		$dbh->exec("DELETE FROM Page");
		$dbh->exec("DELETE FROM Category");
	}
}
