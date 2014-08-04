
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- type_object
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS type_object;

CREATE TABLE type_object
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    details MEDIUMBLOB,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- book
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS book;

CREATE TABLE book
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Book Id',
    title VARCHAR(255) NOT NULL COMMENT 'Book Title',
    isbn VARCHAR(24) NOT NULL COMMENT 'ISBN Number',
    price FLOAT COMMENT 'Price of the book.',
    publisher_id INTEGER COMMENT 'Foreign Key Publisher',
    author_id INTEGER COMMENT 'Foreign Key Author',
    PRIMARY KEY (id),
    INDEX book_fi_35872e (publisher_id),
    INDEX book_fi_ea464c (author_id),
    CONSTRAINT book_fk_35872e
        FOREIGN KEY (publisher_id)
        REFERENCES publisher (id)
        ON DELETE SET NULL,
    CONSTRAINT book_fk_ea464c
        FOREIGN KEY (author_id)
        REFERENCES author (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Book Table';

-- ---------------------------------------------------------------------
-- publisher
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS publisher;

CREATE TABLE publisher
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Publisher Id',
    name VARCHAR(128) DEFAULT 'Penguin' NOT NULL COMMENT 'Publisher Name',
    PRIMARY KEY (id)
) ENGINE=InnoDB COMMENT='Publisher Table';

-- ---------------------------------------------------------------------
-- author
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS author;

CREATE TABLE author
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Author Id',
    first_name VARCHAR(128) NOT NULL COMMENT 'First Name',
    last_name VARCHAR(128) NOT NULL COMMENT 'Last Name',
    email VARCHAR(128) COMMENT 'E-Mail Address',
    age INTEGER COMMENT 'The authors age',
    PRIMARY KEY (id)
) ENGINE=InnoDB COMMENT='Author Table';

-- ---------------------------------------------------------------------
-- book_summary
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS book_summary;

CREATE TABLE book_summary
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    book_id INTEGER NOT NULL,
    summary TEXT NOT NULL,
    PRIMARY KEY (id),
    INDEX book_summary_fi_23450f (book_id),
    CONSTRAINT book_summary_fk_23450f
        FOREIGN KEY (book_id)
        REFERENCES book (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- review
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS review;

CREATE TABLE review
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Review Id',
    reviewed_by VARCHAR(128) NOT NULL COMMENT 'Reviewer Name',
    review_date DATE DEFAULT '2001-01-01' NOT NULL COMMENT 'Date of Review',
    recommended TINYINT(1) NOT NULL COMMENT 'Does reviewer recommend book?',
    status VARCHAR(8) COMMENT 'The status of this review.',
    book_id INTEGER COMMENT 'Book ID for this review',
    PRIMARY KEY (id),
    INDEX review_fi_23450f (book_id),
    CONSTRAINT review_fk_23450f
        FOREIGN KEY (book_id)
        REFERENCES book (id)
        ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Book Review';

-- ---------------------------------------------------------------------
-- essay
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS essay;

CREATE TABLE essay
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    first_author INTEGER COMMENT 'Foreign Key Author',
    second_author INTEGER COMMENT 'Foreign Key Author',
    subtitle VARCHAR(255),
    next_essay_id INTEGER COMMENT 'Book Id',
    PRIMARY KEY (id),
    INDEX essay_fi_f3f051 (first_author),
    INDEX essay_fi_c12058 (second_author),
    INDEX essay_fi_d6b6e9 (next_essay_id),
    CONSTRAINT essay_fk_f3f051
        FOREIGN KEY (first_author)
        REFERENCES author (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT essay_fk_c12058
        FOREIGN KEY (second_author)
        REFERENCES author (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT essay_fk_d6b6e9
        FOREIGN KEY (next_essay_id)
        REFERENCES essay (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- composite_essay
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS composite_essay;

CREATE TABLE composite_essay
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    first_essay_id INTEGER COMMENT 'Book Id',
    second_essay_id INTEGER COMMENT 'Book Id',
    PRIMARY KEY (id),
    INDEX composite_essay_fi_0121b3 (first_essay_id),
    INDEX composite_essay_fi_0754ed (second_essay_id),
    CONSTRAINT composite_essay_fk_0121b3
        FOREIGN KEY (first_essay_id)
        REFERENCES composite_essay (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT composite_essay_fk_0754ed
        FOREIGN KEY (second_essay_id)
        REFERENCES composite_essay (id)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- man
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS man;

CREATE TABLE man
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    wife_id INTEGER,
    PRIMARY KEY (id),
    INDEX man_fi_0d90d0 (wife_id),
    CONSTRAINT man_fk_0d90d0
        FOREIGN KEY (wife_id)
        REFERENCES woman (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- woman
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS woman;

CREATE TABLE woman
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    husband_id INTEGER,
    PRIMARY KEY (id),
    INDEX woman_fi_ef2bc1 (husband_id),
    CONSTRAINT woman_fk_ef2bc1
        FOREIGN KEY (husband_id)
        REFERENCES man (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- media
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS media;

CREATE TABLE media
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Media Id',
    cover_image BLOB COMMENT 'The image of the book cover.',
    excerpt LONGTEXT COMMENT 'An excerpt from the book.',
    book_id INTEGER NOT NULL COMMENT 'Book ID for this media collection.',
    PRIMARY KEY (id),
    INDEX media_fi_23450f (book_id),
    CONSTRAINT media_fk_23450f
        FOREIGN KEY (book_id)
        REFERENCES book (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- book_club_list
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS book_club_list;

CREATE TABLE book_club_list
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Unique ID for a school reading list.',
    group_leader VARCHAR(100) NOT NULL COMMENT 'The name of the teacher in charge of summer reading.',
    theme VARCHAR(50) COMMENT 'The theme, if applicable, for the reading list.',
    created_at DATETIME,
    PRIMARY KEY (id)
) ENGINE=InnoDB COMMENT='Reading list for a book club.';

-- ---------------------------------------------------------------------
-- book_x_list
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS book_x_list;

CREATE TABLE book_x_list
(
    book_id INTEGER NOT NULL COMMENT 'Fkey to book.id',
    book_club_list_id INTEGER NOT NULL COMMENT 'Fkey to book_club_list.id',
    PRIMARY KEY (book_id,book_club_list_id),
    INDEX book_x_list_fi_c91300 (book_club_list_id),
    CONSTRAINT book_x_list_fk_23450f
        FOREIGN KEY (book_id)
        REFERENCES book (id)
        ON DELETE CASCADE,
    CONSTRAINT book_x_list_fk_c91300
        FOREIGN KEY (book_club_list_id)
        REFERENCES book_club_list (id)
        ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Cross-reference table between book and book_club_list rows.';

-- ---------------------------------------------------------------------
-- book_club_list_favorite_books
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS book_club_list_favorite_books;

CREATE TABLE book_club_list_favorite_books
(
    book_id INTEGER NOT NULL COMMENT 'Fkey to book.id',
    book_club_list_id INTEGER NOT NULL COMMENT 'Fkey to book_club_list.id',
    PRIMARY KEY (book_id,book_club_list_id),
    INDEX book_club_list_favorite_books_fi_c91300 (book_club_list_id),
    CONSTRAINT book_club_list_favorite_books_fk_23450f
        FOREIGN KEY (book_id)
        REFERENCES book (id)
        ON DELETE CASCADE,
    CONSTRAINT book_club_list_favorite_books_fk_c91300
        FOREIGN KEY (book_club_list_id)
        REFERENCES book_club_list (id)
        ON DELETE CASCADE
) ENGINE=InnoDB COMMENT='Another cross-reference table for many-to-many relationship between book rows and book_club_list rows for favorite books.';

-- ---------------------------------------------------------------------
-- bookstore_employee
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS bookstore_employee;

CREATE TABLE bookstore_employee
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Employee ID number',
    class_key INTEGER DEFAULT 0 NOT NULL,
    name VARCHAR(32) COMMENT 'Employee name',
    job_title VARCHAR(32) COMMENT 'Employee job title',
    supervisor_id INTEGER COMMENT 'Fkey to supervisor.',
    photo BLOB,
    PRIMARY KEY (id),
    INDEX bookstore_employee_fi_57e71b (supervisor_id),
    CONSTRAINT bookstore_employee_fk_57e71b
        FOREIGN KEY (supervisor_id)
        REFERENCES bookstore_employee (id)
        ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Hierarchical table to represent employees of a bookstore.';

-- ---------------------------------------------------------------------
-- bookstore_employee_account
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS bookstore_employee_account;

CREATE TABLE bookstore_employee_account
(
    employee_id INTEGER NOT NULL COMMENT 'Primary key for the account ...',
    login VARCHAR(32),
    password VARCHAR(100) DEFAULT '\'@\'\'34\"',
    enabled TINYINT(1) DEFAULT 1,
    not_enabled TINYINT(1) DEFAULT 0,
    created TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    role_id INTEGER,
    authenticator VARCHAR(32) DEFAULT 'Password',
    PRIMARY KEY (employee_id),
    UNIQUE INDEX bookstore_employee_account_u_273bbf (login),
    INDEX bookstore_employee_account_fi_451a43 (role_id),
    CONSTRAINT bookstore_employee_account_fk_0ae967
        FOREIGN KEY (employee_id)
        REFERENCES bookstore_employee (id)
        ON DELETE CASCADE,
    CONSTRAINT bookstore_employee_account_fk_451a43
        FOREIGN KEY (role_id)
        REFERENCES acct_access_role (id)
        ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='Bookstore employees login credentials.';

-- ---------------------------------------------------------------------
-- acct_audit_log
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS acct_audit_log;

CREATE TABLE acct_audit_log
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    uid VARCHAR(32) NOT NULL,
    message VARCHAR(255),
    PRIMARY KEY (id),
    UNIQUE INDEX acct_audit_log_u_8eaf8f (uid, message),
    INDEX acct_audit_log_i_6c9d71 (id, uid),
    CONSTRAINT acct_audit_log_fk_ac8738
        FOREIGN KEY (uid)
        REFERENCES bookstore_employee_account (login)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- acct_access_role
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS acct_access_role;

CREATE TABLE acct_access_role
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Role ID number',
    name VARCHAR(25) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- book_reader
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS book_reader;

CREATE TABLE book_reader
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Book reader ID number',
    name VARCHAR(50),
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- book_opinion
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS book_opinion;

CREATE TABLE book_opinion
(
    book_id INTEGER NOT NULL,
    reader_id INTEGER NOT NULL,
    rating DECIMAL,
    recommend_to_friend TINYINT(1),
    PRIMARY KEY (book_id,reader_id),
    INDEX book_opinion_fi_c7b71a (reader_id),
    CONSTRAINT book_opinion_fk_23450f
        FOREIGN KEY (book_id)
        REFERENCES book (id)
        ON DELETE CASCADE,
    CONSTRAINT book_opinion_fk_c7b71a
        FOREIGN KEY (reader_id)
        REFERENCES book_reader (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- reader_favorite
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS reader_favorite;

CREATE TABLE reader_favorite
(
    book_id INTEGER NOT NULL,
    reader_id INTEGER NOT NULL,
    PRIMARY KEY (book_id,reader_id),
    INDEX reader_favorite_fi_c7b71a (reader_id),
    CONSTRAINT reader_favorite_fk_23450f
        FOREIGN KEY (book_id)
        REFERENCES book (id)
        ON DELETE CASCADE,
    CONSTRAINT reader_favorite_fk_c7b71a
        FOREIGN KEY (reader_id)
        REFERENCES book_reader (id)
        ON DELETE CASCADE,
    CONSTRAINT reader_favorite_fk_8abf4c
        FOREIGN KEY (book_id,reader_id)
        REFERENCES book_opinion (book_id,reader_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- bookstore
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS bookstore;

CREATE TABLE bookstore
(
    id INTEGER NOT NULL AUTO_INCREMENT COMMENT 'Book store ID number',
    store_name VARCHAR(50) NOT NULL,
    location VARCHAR(100),
    population_served BIGINT,
    total_books INTEGER,
    store_open_time TIME,
    website VARCHAR(255),
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- bookstore_sale
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS bookstore_sale;

CREATE TABLE bookstore_sale
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    bookstore_id INTEGER DEFAULT 1,
    publisher_id INTEGER,
    sale_name VARCHAR(100),
    discount TINYINT DEFAULT 10 COMMENT 'Discount percentage',
    PRIMARY KEY (id),
    INDEX bookstore_sale_fi_d73164 (bookstore_id),
    INDEX bookstore_sale_fi_35872e (publisher_id),
    CONSTRAINT bookstore_sale_fk_d73164
        FOREIGN KEY (bookstore_id)
        REFERENCES bookstore (id)
        ON DELETE CASCADE,
    CONSTRAINT bookstore_sale_fk_35872e
        FOREIGN KEY (publisher_id)
        REFERENCES publisher (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- customer
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS customer;

CREATE TABLE customer
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    name VARCHAR(255),
    join_date DATE,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- contest
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS contest;

CREATE TABLE contest
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    name VARCHAR(100),
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- contest_view
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS contest_view;

CREATE TABLE contest_view
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    name VARCHAR(100),
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- bookstore_contest
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS bookstore_contest;

CREATE TABLE bookstore_contest
(
    bookstore_id INTEGER NOT NULL,
    contest_id INTEGER NOT NULL,
    prize_book_id INTEGER,
    PRIMARY KEY (bookstore_id,contest_id),
    INDEX bookstore_contest_fi_454c53 (contest_id),
    INDEX bookstore_contest_fi_10510a (prize_book_id),
    CONSTRAINT bookstore_contest_fk_d73164
        FOREIGN KEY (bookstore_id)
        REFERENCES bookstore (id)
        ON DELETE CASCADE,
    CONSTRAINT bookstore_contest_fk_454c53
        FOREIGN KEY (contest_id)
        REFERENCES contest (id)
        ON DELETE CASCADE,
    CONSTRAINT bookstore_contest_fk_10510a
        FOREIGN KEY (prize_book_id)
        REFERENCES book (id)
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- bookstore_contest_entry
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS bookstore_contest_entry;

CREATE TABLE bookstore_contest_entry
(
    bookstore_id INTEGER NOT NULL,
    contest_id INTEGER NOT NULL,
    customer_id INTEGER NOT NULL,
    entry_date TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (bookstore_id,contest_id,customer_id),
    INDEX bookstore_contest_entry_fi_7e8f3e (customer_id),
    CONSTRAINT bookstore_contest_entry_fk_d73164
        FOREIGN KEY (bookstore_id)
        REFERENCES bookstore (id)
        ON DELETE CASCADE,
    CONSTRAINT bookstore_contest_entry_fk_7e8f3e
        FOREIGN KEY (customer_id)
        REFERENCES customer (id)
        ON DELETE CASCADE,
    CONSTRAINT bookstore_contest_entry_fk_f5af06
        FOREIGN KEY (bookstore_id,contest_id)
        REFERENCES bookstore_contest (bookstore_id,contest_id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- book2
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS book2;

CREATE TABLE book2
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    title VARCHAR(255),
    style TINYINT,
    tags TEXT,
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- distribution
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS distribution;

CREATE TABLE distribution
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    name VARCHAR(255),
    type INTEGER DEFAULT 0 NOT NULL,
    distribution_manager_id INTEGER NOT NULL,
    PRIMARY KEY (id),
    INDEX distribution_fi_bbbe1b (distribution_manager_id),
    CONSTRAINT distribution_fk_bbbe1b
        FOREIGN KEY (distribution_manager_id)
        REFERENCES distribution_manager (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- distribution_manager
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS distribution_manager;

CREATE TABLE distribution_manager
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    name VARCHAR(255),
    PRIMARY KEY (id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- record_label
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS record_label;

CREATE TABLE record_label
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    abbr VARCHAR(5) NOT NULL,
    name VARCHAR(255),
    PRIMARY KEY (id,abbr)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- release_pool
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS release_pool;

CREATE TABLE release_pool
(
    id INTEGER NOT NULL AUTO_INCREMENT,
    record_label_id INTEGER NOT NULL,
    name VARCHAR(255),
    PRIMARY KEY (id),
    INDEX release_pool_fi_935c68 (record_label_id),
    CONSTRAINT release_pool_fk_935c68
        FOREIGN KEY (record_label_id)
        REFERENCES record_label (id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
