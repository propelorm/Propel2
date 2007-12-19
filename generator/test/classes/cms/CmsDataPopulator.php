<?php
/*
 *  $Id: BookstoreDataPopulator.php 857 2007-12-13 14:59:59Z heltem $
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
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (1,194,'home')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (2,5,'school')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (6,43,'education')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (44,45,'simulator')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (46,47,'ac')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (3,4,'history')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (7,14,'master-mariner')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (8,9,'education')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (48,85,'courses')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (98,101,'contact')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (10,11,'entrance')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (104,191,'intra')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (102,103,'services')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (12,13,'competency')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (15,22,'watchkeeping-officer')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (16,17,'education')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (18,19,'entrance')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (20,21,'competency')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (31,38,'watchkeeping-engineer')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (32,33,'education')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (34,35,'entrance')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (36,37,'competency')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (39,40,'practice')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (86,97,'news')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (95,96,'2007-02')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (99,100,'personnel')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (87,88,'2007-06')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (49,50,'nautical')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (51,52,'radiotechnical')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (53,54,'resourcemgmt')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (57,58,'safety')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (59,60,'firstaid')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (61,62,'sar')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (67,84,'upcoming')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (65,66,'languages')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (55,56,'cargomgmt')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (119,120,'timetable')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (63,64,'boaters')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (105,118,'bulletinboard')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (106,107,'sdf')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (41,42,'fristaende')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (23,30,'ingenj')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (24,25,'utbildn')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (26,27,'ansokn')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (93,94,'utexaminerade')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (89,92,'Massan')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (192,193,'lankar')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (68,69,'FRB')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (70,71,'pelastautumis')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (72,73,'CCM')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (74,75,'sjukvard')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (121,188,'Veckoscheman')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (134,135,'VS3VSVsjukv')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (122,123,'sjoarb')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (130,131,'fysik1')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (140,141,'kemi')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (76,77,'inr')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (78,79,'forare')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (144,145,'AlexandraYH2')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (132,133,'AlexandraVS2')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (80,81,'Maskin')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (126,127,'forstahjalp')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (136,137,'Juridik')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (142,143,'mate')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (82,83,'basic')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (124,125,'mask')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (108,109,'magnus')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (138,139,'sjosakerhet')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (28,29,'pate')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (148,149,'eng')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (146,147,'forstahjalpYH1')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (110,111,'kortoverlevnadskurs')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (158,159,'kortoverlevnadskurs')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (128,129,'metall')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (152,153,'fysik')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (156,157,'fardplan')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (154,155,'astro')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (90,91,'utstallare')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (150,151,'eng')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (160,161,'ent')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (162,163,'juridik')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (168,169,'svenska')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (164,165,'matemat')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (166,167,'operativa')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (170,171,'plan')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (172,173,'src')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (112,113,'sjukv')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (174,175,'matemati')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (176,177,'fysiikka')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (114,115,'hantv')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (116,117,'CCM')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (178,179,'haveri')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (180,181,'FRB')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (182,183,'kemia')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (184,185,'vaktrutiner')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (189,190,'laroplan')");
		$dbh->exec("INSERT INTO Page (LeftChild, RightChild, Title) VALUES (186,187,'SSOkurs')");
	}

	public static function depopulate()
	{
		$dbh = Propel::getConnection(PagePeer::DATABASE_NAME);
		$dbh->exec("DELETE FROM Page");
	}

}
