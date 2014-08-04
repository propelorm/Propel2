
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- add_class_table
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS add_class_table;

CREATE TABLE add_class_table
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(100),
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- validate_book
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS validate_book;

CREATE TABLE validate_book
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Book Id',
    title VARCHAR(255) NOT NULL COMMENT 'Book Title',
    isbn VARCHAR(24) COMMENT 'ISBN Number',
    price FLOAT COMMENT 'Price of the book.',
    publisher_id INTEGER COMMENT 'Foreign Key Publisher',
    author_id INTEGER COMMENT 'Foreign Key Author',
    PRIMARY KEY (id),
    INDEX validate_book_fi_adc535 (publisher_id),
    INDEX validate_book_fi_110e2f (author_id),
    CONSTRAINT validate_book_fk_adc535
        FOREIGN KEY (publisher_id)
        REFERENCES validate_publisher (id)
        ON DELETE SET NULL,
    CONSTRAINT validate_book_fk_110e2f
        FOREIGN KEY (author_id)
        REFERENCES validate_author (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Book Table';

-- ---------------------------------------------------------------------
-- validate_publisher
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS validate_publisher;

CREATE TABLE validate_publisher
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Publisher Id',
    name VARCHAR(128) DEFAULT 'Penguin' NOT NULL COMMENT 'Publisher Name',
    website VARCHAR(255) COMMENT 'Publisher\'s web site',
    PRIMARY KEY (id)
) ENGINE=InnoDB COMMENT='Publisher Table';

-- ---------------------------------------------------------------------
-- validate_author
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS validate_author;

CREATE TABLE validate_author
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Author Id',
    first_name VARCHAR(128) NOT NULL COMMENT 'First Name',
    last_name VARCHAR(128) NOT NULL COMMENT 'Last Name',
    email VARCHAR(128) COMMENT 'E-Mail Address',
    birthday DATE COMMENT 'The authors birthday',
    PRIMARY KEY (id)
) ENGINE=InnoDB COMMENT='Author Table';

-- ---------------------------------------------------------------------
-- validate_reader
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS validate_reader;

CREATE TABLE validate_reader
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Author Id',
    first_name VARCHAR(128) NOT NULL COMMENT 'First Name',
    last_name VARCHAR(128) NOT NULL COMMENT 'Last Name',
    email VARCHAR(128) COMMENT 'E-Mail Address',
    birthday DATE COMMENT 'The authors birthday',
    PRIMARY KEY (id)
) ENGINE=InnoDB COMMENT='Reader Table';

-- ---------------------------------------------------------------------
-- validate_reader_book
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS validate_reader_book;

CREATE TABLE validate_reader_book
(
    reader_id INTEGER NOT NULL,
    book_id INTEGER NOT NULL,
    PRIMARY KEY (reader_id,book_id),
    INDEX validate_reader_book_fi_6d123c (book_id),
    CONSTRAINT validate_reader_book_fk_7a3564
        FOREIGN KEY (reader_id)
        REFERENCES validate_reader (id),
    CONSTRAINT validate_reader_book_fk_6d123c
        FOREIGN KEY (book_id)
        REFERENCES validate_book (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- querycache_table1
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS querycache_table1;

CREATE TABLE querycache_table1
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(100),
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- table6
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS table6;

CREATE TABLE table6
(
    title VARCHAR(100),
    id INTEGER NOT NULL AUTO_INCREMENT,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- table7
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS table7;

CREATE TABLE table7
(
    foo INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(100),
    PRIMARY KEY (foo)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- table8
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS table8;

CREATE TABLE table8
(
    title VARCHAR(100),
    foo_id INTEGER,
    identifier BIGINT NOT NULL,
    PRIMARY KEY (identifier),
    INDEX table8_fi_3abc8b (foo_id),
    CONSTRAINT table8_fk_3abc8b
        FOREIGN KEY (foo_id)
        REFERENCES table6 (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- aggregate_post
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS aggregate_post;

CREATE TABLE aggregate_post
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    nb_comments INTEGER,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- aggregate_comment
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS aggregate_comment;

CREATE TABLE aggregate_comment
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    post_id INTEGER,
    PRIMARY KEY (id),
    INDEX aggregate_comment_fi_9da9c8 (post_id),
    CONSTRAINT aggregate_comment_fk_9da9c8
        FOREIGN KEY (post_id)
        REFERENCES aggregate_post (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- aggregate_poll
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS aggregate_poll;

CREATE TABLE aggregate_poll
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    total_score INTEGER,
    nb_votes INTEGER,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- aggregate_item
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS aggregate_item;

CREATE TABLE aggregate_item
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    score INTEGER DEFAULT 0,
    poll_id INTEGER,
    PRIMARY KEY (id),
    INDEX aggregate_item_fi_7575bc (poll_id),
    CONSTRAINT aggregate_item_fk_7575bc
        FOREIGN KEY (poll_id)
        REFERENCES aggregate_poll (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- table3
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS table3;

CREATE TABLE table3
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(100),
    test DATETIME,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- table13
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS table13;

CREATE TABLE table13
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(100),
    slug VARCHAR(255),
    PRIMARY KEY (id),
    UNIQUE INDEX table13_slug (slug(255))
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- table14
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS table14;

CREATE TABLE table14
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(100),
    url VARCHAR(100),
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- table_with_scope
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS table_with_scope;

CREATE TABLE table_with_scope
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    scope INTEGER,
    title VARCHAR(100),
    slug VARCHAR(255),
    PRIMARY KEY (id),
    UNIQUE INDEX table_with_scope_slug (slug(255), scope)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- sortable_table11
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS sortable_table11;

CREATE TABLE sortable_table11
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(100),
    sortable_rank INTEGER,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- sortable_table12
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS sortable_table12;

CREATE TABLE sortable_table12
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(100),
    position INTEGER,
    my_scope_column INTEGER,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- sortable_multi_scopes
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS sortable_multi_scopes;

CREATE TABLE sortable_multi_scopes
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    category_id INTEGER NOT NULL,
    sub_category_id INTEGER,
    title VARCHAR(100),
    position INTEGER,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- sortable_multi_comma_scopes
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS sortable_multi_comma_scopes;

CREATE TABLE sortable_multi_comma_scopes
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    category_id INTEGER NOT NULL,
    sub_category_id INTEGER,
    title VARCHAR(100),
    position INTEGER,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- table1
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS table1;

CREATE TABLE table1
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(100),
    created_on DATETIME,
    updated_on DATETIME,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- table2
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS table2;

CREATE TABLE table2
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(100),
    created_at DATETIME,
    updated_at DATETIME,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- validate_trigger_book
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS validate_trigger_book;

CREATE TABLE validate_trigger_book
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Book Id',
    isbn VARCHAR(24) COMMENT 'ISBN Number',
    price FLOAT COMMENT 'Price of the book.',
    publisher_id INTEGER COMMENT 'Foreign Key Publisher',
    author_id INTEGER COMMENT 'Foreign Key Author',
    descendant_class VARCHAR(100),
    PRIMARY KEY (id)
) ENGINE=InnoDB COMMENT='Book Table';

-- ---------------------------------------------------------------------
-- validate_trigger_fiction
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS validate_trigger_fiction;

CREATE TABLE validate_trigger_fiction
(
    foo VARCHAR(100),
    id INTEGER NOT NULL COMMENT 'Book Id',
    isbn VARCHAR(24) COMMENT 'ISBN Number',
    price FLOAT COMMENT 'Price of the book.',
    publisher_id INTEGER COMMENT 'Foreign Key Publisher',
    author_id INTEGER COMMENT 'Foreign Key Author',
    PRIMARY KEY (id),
    CONSTRAINT validate_trigger_fiction_fk_c65579
        FOREIGN KEY (id)
        REFERENCES validate_trigger_book (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- validate_trigger_comic
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS validate_trigger_comic;

CREATE TABLE validate_trigger_comic
(
    bar VARCHAR(100),
    id INTEGER NOT NULL COMMENT 'Book Id',
    isbn VARCHAR(24) COMMENT 'ISBN Number',
    price FLOAT COMMENT 'Price of the book.',
    publisher_id INTEGER COMMENT 'Foreign Key Publisher',
    author_id INTEGER COMMENT 'Foreign Key Author',
    PRIMARY KEY (id),
    CONSTRAINT validate_trigger_comic_fk_c65579
        FOREIGN KEY (id)
        REFERENCES validate_trigger_book (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- validate_trigger_book_i18n
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS validate_trigger_book_i18n;

CREATE TABLE validate_trigger_book_i18n
(
    id INTEGER NOT NULL COMMENT 'Book Id',
    locale VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    title VARCHAR(255) NOT NULL COMMENT 'Book Title',
    PRIMARY KEY (id,locale),
    CONSTRAINT validate_trigger_book_i18n_fk_c65579
        FOREIGN KEY (id)
        REFERENCES validate_trigger_book (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- validate_trigger_fiction_i18n
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS validate_trigger_fiction_i18n;

CREATE TABLE validate_trigger_fiction_i18n
(
    id INTEGER NOT NULL COMMENT 'Book Id',
    locale VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    title VARCHAR(255) NOT NULL COMMENT 'Book Title',
    PRIMARY KEY (id,locale),
    CONSTRAINT validate_trigger_fiction_i18n_fk_e7bb81
        FOREIGN KEY (id)
        REFERENCES validate_trigger_fiction (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- validate_trigger_comic_i18n
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS validate_trigger_comic_i18n;

CREATE TABLE validate_trigger_comic_i18n
(
    id INTEGER NOT NULL COMMENT 'Book Id',
    locale VARCHAR(5) DEFAULT 'en_US' NOT NULL,
    title VARCHAR(255) NOT NULL COMMENT 'Book Title',
    PRIMARY KEY (id,locale),
    CONSTRAINT validate_trigger_comic_i18n_fk_b388e4
        FOREIGN KEY (id)
        REFERENCES validate_trigger_comic (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- concrete_category
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS concrete_category;

CREATE TABLE concrete_category
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    name VARCHAR(100),
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- concrete_content
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS concrete_content;

CREATE TABLE concrete_content
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(100),
    category_id INTEGER,
    descendant_class VARCHAR(100),
    PRIMARY KEY (id),
    INDEX concrete_content_i_639136 (title),
    INDEX concrete_content_fi_e9e7f1 (category_id),
    CONSTRAINT concrete_content_fk_e9e7f1
        FOREIGN KEY (category_id)
        REFERENCES concrete_category (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- concrete_article
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS concrete_article;

CREATE TABLE concrete_article
(
    body TEXT,
    author_id INTEGER,
    id INTEGER NOT NULL,
    title VARCHAR(100),
    category_id INTEGER,
    descendant_class VARCHAR(100),
    PRIMARY KEY (id),
    INDEX concrete_article_fi_1e33e8 (author_id),
    INDEX concrete_article_i_639136 (title),
    INDEX concrete_article_i_916b34 (category_id),
    CONSTRAINT concrete_article_fk_1e33e8
        FOREIGN KEY (author_id)
        REFERENCES concrete_author (id)
        ON DELETE CASCADE,
    CONSTRAINT concrete_article_fk_5f35e2
        FOREIGN KEY (id)
        REFERENCES concrete_content (id)
        ON DELETE CASCADE,
    CONSTRAINT concrete_article_fk_e9e7f1
        FOREIGN KEY (category_id)
        REFERENCES concrete_category (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- concrete_author
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS concrete_author;

CREATE TABLE concrete_author
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    name VARCHAR(100),
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- concrete_news
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS concrete_news;

CREATE TABLE concrete_news
(
    body TEXT,
    author_id INTEGER,
    id INTEGER NOT NULL,
    title VARCHAR(100),
    category_id INTEGER,
    PRIMARY KEY (id),
    INDEX concrete_news_i_3ea699 (author_id),
    INDEX concrete_news_i_639136 (title),
    INDEX concrete_news_i_916b34 (category_id),
    CONSTRAINT concrete_news_fk_7ab554
        FOREIGN KEY (id)
        REFERENCES concrete_article (id)
        ON DELETE CASCADE,
    CONSTRAINT concrete_news_fk_1e33e8
        FOREIGN KEY (author_id)
        REFERENCES concrete_author (id)
        ON DELETE CASCADE,
    CONSTRAINT concrete_news_fk_5f35e2
        FOREIGN KEY (id)
        REFERENCES concrete_content (id)
        ON DELETE CASCADE,
    CONSTRAINT concrete_news_fk_e9e7f1
        FOREIGN KEY (category_id)
        REFERENCES concrete_category (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- concrete_quizz
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS concrete_quizz;

CREATE TABLE concrete_quizz
(
    title VARCHAR(200),
    id INTEGER NOT NULL AUTO_INCREMENT,
    category_id INTEGER,
    PRIMARY KEY (id),
    INDEX concrete_quizz_i_639136 (title),
    INDEX concrete_quizz_i_916b34 (category_id),
    CONSTRAINT concrete_quizz_fk_e9e7f1
        FOREIGN KEY (category_id)
        REFERENCES concrete_category (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- concrete_quizz_question
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS concrete_quizz_question;

CREATE TABLE concrete_quizz_question
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    question VARCHAR(100),
    answer_1 VARCHAR(100),
    answer_2 VARCHAR(100),
    correct_answer INTEGER,
    quizz_id INTEGER NOT NULL,
    PRIMARY KEY (id),
    INDEX concrete_quizz_question_fi_9d0d27 (quizz_id),
    CONSTRAINT concrete_quizz_question_fk_9d0d27
        FOREIGN KEY (quizz_id)
        REFERENCES concrete_quizz (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
