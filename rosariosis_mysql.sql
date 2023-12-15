--
-- MySQL database dump
--
-- Note: database charset must be utf8mb4 and collation must be utf8mb4_unicode_520_ci
-- Fix illegal mix of collations error for views
--

SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_520_ci';
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
-- FJ No auto increment value when 0 is given as an ID.
-- Used to insert "No Address" inside address table.
-- and "Student" inside user_profiles table.
SET @@sql_mode=CONCAT(@@sql_mode,',NO_AUTO_VALUE_ON_ZERO');

-- Always use InnoDB (default), avoid MyISAM
SET default_storage_engine=InnoDB;

--
-- Name: calc_cum_cr_gpa(mp_id integer, s_id integer); Type: FUNCTION;
-- @since 11.1 SQL set min Credits to 0 & fix division by zero error
--

DELIMITER $$
CREATE PROCEDURE calc_cum_cr_gpa(mp_id integer, s_id integer)
BEGIN
    UPDATE student_mp_stats
    SET cum_cr_weighted_factor = (case when cr_credits = '0' THEN '0' ELSE cr_weighted_factors/cr_credits END),
        cum_cr_unweighted_factor = (case when cr_credits = '0' THEN '0' ELSE cr_unweighted_factors/cr_credits END)
    WHERE student_mp_stats.student_id = s_id and student_mp_stats.marking_period_id = mp_id;
END$$
DELIMITER ;


--
-- Name: calc_cum_gpa(mp_id integer, s_id integer); Type: FUNCTION;
-- @since 11.1 SQL set min Credits to 0 & fix division by zero error
--

DELIMITER $$
CREATE PROCEDURE calc_cum_gpa(mp_id integer, s_id integer)
BEGIN
    UPDATE student_mp_stats
    SET cum_weighted_factor = (case when gp_credits = '0' THEN '0' ELSE sum_weighted_factors/gp_credits END),
        cum_unweighted_factor = (case when gp_credits = '0' THEN '0' ELSE sum_unweighted_factors/gp_credits END)
    WHERE student_mp_stats.student_id = s_id and student_mp_stats.marking_period_id = mp_id;
END$$
DELIMITER ;


--
-- Name: calc_gpa_mp(s_id integer, mp_id integer); Type: FUNCTION;
--
-- @link https://stackoverflow.com/questions/9845171/run-a-query-in-a-mysql-stored-procedure-if-a-condition-is-true
--

DELIMITER $$
CREATE PROCEDURE calc_gpa_mp(s_id integer, mp_id integer)
BEGIN
    DECLARE oldrec integer;

    SELECT count(*) INTO oldrec FROM student_mp_stats WHERE student_id = s_id and marking_period_id = mp_id;

    IF oldrec > 0 THEN
    UPDATE student_mp_stats sms
    JOIN (
        select
        student_id,
        marking_period_id,
        sum(weighted_gp*credit_attempted/gp_scale) as sum_weighted_factors,
        sum(unweighted_gp*credit_attempted/gp_scale) as sum_unweighted_factors,
        sum(credit_attempted) as gp_credits,
        sum( case when class_rank = 'Y' THEN weighted_gp*credit_attempted/gp_scale END ) as cr_weighted,
        sum( case when class_rank = 'Y' THEN unweighted_gp*credit_attempted/gp_scale END ) as cr_unweighted,
        sum( case when class_rank = 'Y' THEN credit_attempted END) as cr_credits

        from student_report_card_grades
        where student_id = s_id
        and marking_period_id = mp_id
        and not gp_scale = 0
        group by student_id, marking_period_id
    ) as rcg
    ON rcg.student_id = sms.student_id and rcg.marking_period_id = sms.marking_period_id
    SET
        sms.sum_weighted_factors = rcg.sum_weighted_factors,
        sms.sum_unweighted_factors = rcg.sum_unweighted_factors,
        sms.cr_weighted_factors = rcg.cr_weighted,
        sms.cr_unweighted_factors = rcg.cr_unweighted,
        sms.gp_credits = rcg.gp_credits,
        sms.cr_credits = rcg.cr_credits;

    ELSE
    INSERT INTO student_mp_stats (student_id, marking_period_id, sum_weighted_factors, sum_unweighted_factors, grade_level_short, cr_weighted_factors, cr_unweighted_factors, gp_credits, cr_credits)

        select
            srcg.student_id,
            srcg.marking_period_id,
            sum(weighted_gp*credit_attempted/gp_scale) as sum_weighted_factors,
            sum(unweighted_gp*credit_attempted/gp_scale) as sum_unweighted_factors,
            (select eg.short_name
                from enroll_grade eg, marking_periods mp
                where eg.student_id = s_id
                and eg.syear = mp.syear
                and eg.school_id = mp.school_id
                and eg.start_date <= mp.end_date
                and mp.marking_period_id = mp_id
                order by eg.start_date desc
                limit 1) as short_name,
            sum( case when class_rank = 'Y' THEN weighted_gp*credit_attempted/gp_scale END ) as cr_weighted,
            sum( case when class_rank = 'Y' THEN unweighted_gp*credit_attempted/gp_scale END ) as cr_unweighted,
            sum(credit_attempted) as gp_credits,
            sum(case when class_rank = 'Y' THEN credit_attempted END) as cr_credits
        from student_report_card_grades srcg
        where srcg.student_id = s_id and srcg.marking_period_id = mp_id and not srcg.gp_scale = 0
        group by srcg.student_id, srcg.marking_period_id, short_name;
    END IF;
END$$
DELIMITER ;

--
-- Name: credit(cp_id integer, mp_id integer); Type: FUNCTION;
--

DELIMITER $$
CREATE FUNCTION credit(cp_id integer, mp_id integer) RETURNS numeric(6,2)
BEGIN
    DECLARE course_detail_mp_id integer;
    DECLARE course_detail_mp varchar(3);
    DECLARE course_detail_credits numeric(6,2);
    DECLARE mp_detail_mp_id integer;
    DECLARE mp_detail_mp_type varchar(20);
    DECLARE val_mp_count integer;

    select marking_period_id,mp,credits into course_detail_mp_id,course_detail_mp,course_detail_credits from course_periods where course_period_id = cp_id;
    select marking_period_id,mp_type into mp_detail_mp_id,mp_detail_mp_type from marking_periods where marking_period_id = mp_id;

    IF course_detail_mp_id = mp_detail_mp_id THEN
        RETURN course_detail_credits;
    ELSEIF course_detail_mp = 'FY' AND mp_detail_mp_type = 'semester' THEN
        select count(*) into val_mp_count from marking_periods where parent_id = course_detail_mp_id group by parent_id;
    ELSEIF course_detail_mp = 'FY' and mp_detail_mp_type = 'quarter' THEN
        select count(*) into val_mp_count from marking_periods where grandparent_id = course_detail_mp_id group by grandparent_id;
    ELSEIF course_detail_mp = 'SEM' and mp_detail_mp_type = 'quarter' THEN
        select count(*) into val_mp_count from marking_periods where parent_id = course_detail_mp_id group by parent_id;
    ELSE
        RETURN course_detail_credits;
    END IF;

    IF val_mp_count > 0 THEN
        RETURN course_detail_credits/val_mp_count;
    ELSE
        RETURN course_detail_credits;
    END IF;
END$$
DELIMITER ;


--
-- Name: set_class_rank_mp(mp_id integer); Type: FUNCTION;
-- @since 10.8.2 Fix MySQL 8 syntax error, 'rank' is a reserved keyword
--

DELIMITER $$
CREATE FUNCTION set_class_rank_mp(mp_id integer) RETURNS integer
BEGIN
    update student_mp_stats sms
    JOIN (
        select mp.marking_period_id, sgm.student_id,
        (select count(*)+1
            from student_mp_stats sgm3
            where sgm3.cum_cr_weighted_factor > sgm.cum_cr_weighted_factor
            and sgm3.marking_period_id = mp.marking_period_id
            and sgm3.student_id in (select distinct sgm2.student_id
                from student_mp_stats sgm2, student_enrollment se2
                where sgm2.student_id = se2.student_id
                and sgm2.marking_period_id = mp.marking_period_id
                and se2.grade_id = se.grade_id
                and se2.syear = se.syear)) as class_rank,
        (select count(*)
            from student_mp_stats sgm4
            where sgm4.marking_period_id = mp.marking_period_id
            and sgm4.student_id in (select distinct sgm5.student_id
                from student_mp_stats sgm5, student_enrollment se3
                where sgm5.student_id = se3.student_id
                and sgm5.marking_period_id = mp.marking_period_id
                and se3.grade_id = se.grade_id
                and se3.syear = se.syear)) as class_size
        from student_enrollment se, student_mp_stats sgm, marking_periods mp
        where se.student_id = sgm.student_id
        and sgm.marking_period_id = mp.marking_period_id
        and mp.marking_period_id = mp_id
        and se.syear = mp.syear
        and not sgm.cum_cr_weighted_factor is null
    ) as class_rank
    ON sms.marking_period_id = class_rank.marking_period_id and sms.student_id = class_rank.student_id
    set sms.cum_rank = class_rank.class_rank, sms.class_size = class_rank.class_size;
    RETURN 1;
END$$
DELIMITER ;


--
-- Name: t_update_mp_stats(s_id integer, mp_id integer); Type: FUNCTION;
--

DELIMITER $$
CREATE PROCEDURE t_update_mp_stats(s_id integer, mp_id integer)
BEGIN
    CALL calc_gpa_mp(s_id, mp_id);
    CALL calc_cum_gpa(mp_id, s_id);
    CALL calc_cum_cr_gpa(mp_id, s_id);
END$$
DELIMITER ;


--
-- Name: schools; Type: TABLE;
--

CREATE TABLE schools (
    syear numeric(4,0) NOT NULL,
    id integer NOT NULL AUTO_INCREMENT,
    title varchar(100) NOT NULL,
    address varchar(100),
    city varchar(100),
    state varchar(10),
    zipcode varchar(10),
    phone varchar(30),
    principal varchar(100),
    www_address text,
    school_number varchar(50),
    short_name varchar(25),
    reporting_gp_scale numeric(10,3),
    number_days_rotation numeric(1,0),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (id, syear)
);


--
-- Name: students; Type: TABLE;
--

CREATE TABLE students (
    student_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    last_name varchar(50) NOT NULL,
    first_name varchar(50) NOT NULL,
    middle_name varchar(50),
    name_suffix varchar(3),
    username varchar(100) UNIQUE,
    password varchar(106),
    last_login datetime,
    failed_login integer,
    custom_200000000 text,
    custom_200000001 text,
    custom_200000002 text,
    custom_200000003 text,
    custom_200000004 date,
    custom_200000005 text,
    custom_200000006 text,
    custom_200000007 text,
    custom_200000008 text,
    custom_200000009 longtext,
    custom_200000010 char(1),
    custom_200000011 longtext,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: staff; Type: TABLE;
--

CREATE TABLE staff (
    syear numeric(4,0) NOT NULL,
    staff_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    current_school_id integer,
    title varchar(5),
    first_name varchar(100) NOT NULL,
    last_name varchar(100) NOT NULL,
    middle_name varchar(100),
    name_suffix varchar(3),
    username varchar(100),
    password varchar(106),
    email varchar(255),
    custom_200000001 text, -- Old phone column.
    profile varchar(30),
    homeroom varchar(5),
    schools varchar(150),
    last_login datetime,
    failed_login integer,
    profile_id integer,
    rollover_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: school_marking_periods; Type: TABLE;
--

CREATE TABLE school_marking_periods (
    marking_period_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    mp varchar(3) NOT NULL,
    school_id integer NOT NULL,
    parent_id integer,
    title varchar(50) NOT NULL,
    short_name varchar(10),
    sort_order numeric,
    start_date date NOT NULL,
    end_date date NOT NULL,
    post_start_date date,
    post_end_date date,
    does_grades varchar(1),
    does_comments varchar(1),
    rollover_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: courses; Type: TABLE;
--

CREATE TABLE courses (
    syear numeric(4,0) NOT NULL,
    course_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    subject_id integer NOT NULL,
    school_id integer NOT NULL,
    grade_level integer,
    title varchar(100) NOT NULL,
    short_name varchar(25),
    rollover_id integer,
    credit_hours numeric(6,2),
    description longtext,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: course_periods; Type: TABLE;
--

CREATE TABLE course_periods (
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    course_period_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    course_id integer NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    title text,
    short_name varchar(25) NOT NULL,
    mp varchar(3),
    marking_period_id integer NOT NULL,
    FOREIGN KEY (marking_period_id) REFERENCES school_marking_periods(marking_period_id),
    teacher_id integer NOT NULL,
    FOREIGN KEY (teacher_id) REFERENCES staff(staff_id),
    secondary_teacher_id integer,
    FOREIGN KEY (secondary_teacher_id) REFERENCES staff(staff_id),
    room varchar(10),
    total_seats numeric,
    filled_seats numeric,
    does_attendance text,
    does_honor_roll varchar(1),
    does_class_rank varchar(1),
    gender_restriction varchar(1),
    house_restriction varchar(1),
    availability numeric,
    parent_id integer,
    calendar_id integer,
    half_day varchar(1), -- @deprecated since 8.9
    does_breakoff varchar(1),
    rollover_id integer,
    grade_scale_id integer,
    credits numeric(6,2),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: access_log; Type: TABLE;
--

CREATE TABLE access_log (
    syear numeric(4,0) NOT NULL,
    username varchar(100),
    profile varchar(30),
    login_time datetime, -- @deprecated since 11.0 use created_at instead
    ip_address varchar(50),
    user_agent text,
    status varchar(50),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: accounting_incomes; Type: TABLE;
--

CREATE TABLE accounting_categories (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    title text NOT NULL,
    short_name varchar(10),
    type varchar(100),
    sort_order numeric,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: accounting_incomes; Type: TABLE;
--

CREATE TABLE accounting_incomes (
    assigned_date date,
    comments text,
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title text NOT NULL,
    category_id integer,
    FOREIGN KEY (category_id) REFERENCES accounting_categories(id),
    amount numeric(14,2) NOT NULL,
    file_attached text,
    school_id integer NOT NULL,
    syear numeric(4,0) NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: accounting_salaries; Type: TABLE;
--

CREATE TABLE accounting_salaries (
    staff_id integer NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    assigned_date date,
    due_date date,
    comments text,
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title text NOT NULL,
    amount numeric(14,2) NOT NULL,
    file_attached text,
    school_id integer NOT NULL,
    syear numeric(4,0) NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: accounting_payments; Type: TABLE;
--

CREATE TABLE accounting_payments (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    staff_id integer,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    title text,
    category_id integer,
    FOREIGN KEY (category_id) REFERENCES accounting_categories(id),
    amount numeric(14,2) NOT NULL,
    payment_date date,
    comments text,
    file_attached text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: address; Type: TABLE;
--

CREATE TABLE address (
    address_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    house_no numeric(5,0),
    direction varchar(2),
    street varchar(30),
    apt varchar(5),
    zipcode varchar(10),
    city text,
    state varchar(50),
    mail_street varchar(30),
    mail_city text,
    mail_state varchar(50),
    mail_zipcode varchar(10),
    address text,
    mail_address text,
    phone varchar(30),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: address_field_categories; Type: TABLE;
--

CREATE TABLE address_field_categories (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title text NOT NULL,
    sort_order numeric,
    residence char(1),
    mailing char(1),
    bus char(1),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: address_fields; Type: TABLE;
--

CREATE TABLE address_fields (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type varchar(10) NOT NULL,
    title text NOT NULL,
    sort_order numeric,
    select_options text,
    category_id integer,
    required varchar(1),
    default_selection text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: attendance_calendar; Type: TABLE;
--

CREATE TABLE attendance_calendar (
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    school_date date NOT NULL,
    minutes integer,
    block varchar(10),
    calendar_id integer NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (syear, school_id, school_date, calendar_id),
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: attendance_calendars; Type: TABLE;
--

CREATE TABLE attendance_calendars (
    school_id integer NOT NULL,
    title varchar(100) NOT NULL,
    syear numeric(4,0) NOT NULL,
    calendar_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    default_calendar varchar(1),
    rollover_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: attendance_code_categories; Type: TABLE;
--

CREATE TABLE attendance_code_categories (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    title text NOT NULL,
    sort_order numeric,
    rollover_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: attendance_codes; Type: TABLE;
--

CREATE TABLE attendance_codes (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    title text NOT NULL,
    short_name varchar(10),
    type varchar(10),
    state_code varchar(1),
    default_code varchar(1),
    table_name integer,
    sort_order numeric,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: attendance_completed; Type: TABLE;
--

CREATE TABLE attendance_completed (
    staff_id integer NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    school_date date NOT NULL,
    period_id integer NOT NULL,
    table_name integer NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (staff_id, school_date, period_id, table_name)
);


--
-- Name: attendance_day; Type: TABLE;
--

CREATE TABLE attendance_day (
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    school_date date NOT NULL,
    minutes_present integer,
    state_value numeric(2,1),
    syear numeric(4,0),
    marking_period_id integer,
    FOREIGN KEY (marking_period_id) REFERENCES school_marking_periods(marking_period_id),
    comment text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (student_id, school_date)
);


--
-- Name: attendance_period; Type: TABLE;
--

CREATE TABLE attendance_period (
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    school_date date NOT NULL,
    period_id integer NOT NULL,
    attendance_code integer,
    attendance_teacher_code integer,
    attendance_reason varchar(100),
    admin varchar(1),
    course_period_id integer,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id),
    marking_period_id integer,
    FOREIGN KEY (marking_period_id) REFERENCES school_marking_periods(marking_period_id),
    comment varchar(100),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (student_id, school_date, period_id)
);


--
-- Name: billing_fees; Type: TABLE;
--

CREATE TABLE billing_fees (
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    assigned_date date,
    due_date date,
    comments text,
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title text NOT NULL,
    amount numeric(14,2) NOT NULL,
    file_attached text,
    school_id integer NOT NULL,
    syear numeric(4,0) NOT NULL,
    waived_fee_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    created_by text,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: billing_payments; Type: TABLE;
--

CREATE TABLE billing_payments (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    amount numeric(14,2) NOT NULL,
    payment_date date,
    comments text,
    refunded_payment_id integer,
    lunch_payment varchar(1),
    file_attached text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    created_by text,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: calendar_events; Type: TABLE;
--

CREATE TABLE calendar_events (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    school_date date,
    title varchar(50) NOT NULL,
    description longtext,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: config; Type: TABLE;
--

CREATE TABLE config (
    school_id integer NOT NULL, -- Can be 0.
    title varchar(100) NOT NULL,
    config_value longtext,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: course_details; Type: VIEW;
--

CREATE VIEW course_details AS
    SELECT cp.school_id, cp.syear, cp.marking_period_id, c.subject_id, cp.course_id, cp.course_period_id, cp.teacher_id, c.title AS course_title, cp.title AS cp_title, cp.grade_scale_id, cp.mp, cp.credits FROM course_periods cp, courses c WHERE (cp.course_id = c.course_id);


--
-- Name: course_period_school_periods; Type: TABLE;
--

CREATE TABLE course_period_school_periods (
    course_period_school_periods_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    course_period_id integer NOT NULL,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id),
    period_id integer NOT NULL,
    days varchar(7),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    UNIQUE (course_period_id, period_id)
);


--
-- Name: course_subjects; Type: TABLE;
--

CREATE TABLE course_subjects (
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    subject_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title varchar(100) NOT NULL,
    short_name varchar(25),
    sort_order numeric,
    rollover_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: custom_fields; Type: TABLE;
--

CREATE TABLE custom_fields (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type varchar(10) NOT NULL,
    title text NOT NULL,
    sort_order numeric,
    select_options text,
    category_id integer,
    required varchar(1),
    default_selection text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: discipline_field_usage; Type: TABLE;
--

CREATE TABLE discipline_field_usage (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    discipline_field_id integer NOT NULL,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    title text NOT NULL,
    select_options text,
    sort_order numeric,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: discipline_fields; Type: TABLE;
--

CREATE TABLE discipline_fields (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title text NOT NULL,
    short_name varchar(20),
    data_type varchar(30) NOT NULL,
    column_name text NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: discipline_referrals; Type: TABLE;
--

CREATE TABLE discipline_referrals (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    school_id integer NOT NULL,
    staff_id integer,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    entry_date date,
    referral_date date,
    category_1 text,
    category_2 text,
    category_3 varchar(1),
    category_4 text,
    category_5 text,
    category_6 longtext,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: eligibility; Type: TABLE;
--

CREATE TABLE eligibility (
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    syear numeric(4,0),
    school_date date,
    period_id integer,
    eligibility_code varchar(20),
    course_period_id integer NOT NULL,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: eligibility_activities; Type: TABLE;
--

CREATE TABLE eligibility_activities (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    title text NOT NULL,
    start_date date,
    end_date date,
    comment text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: eligibility_completed; Type: TABLE;
--

CREATE TABLE eligibility_completed (
    staff_id integer NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    school_date date NOT NULL,
    period_id integer NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (staff_id, school_date, period_id)
);


--
-- Name: food_service_accounts; Type: TABLE;
--

CREATE TABLE food_service_accounts (
    account_id integer PRIMARY KEY,
    balance numeric(9,2) NOT NULL,
    transaction_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: food_service_categories; Type: TABLE;
--

CREATE TABLE food_service_categories (
    category_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    menu_id integer NOT NULL,
    title varchar(25) NOT NULL,
    sort_order numeric,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: food_service_items; Type: TABLE;
--

CREATE TABLE food_service_items (
    item_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    short_name varchar(25),
    sort_order numeric,
    description varchar(25),
    icon varchar(50),
    price numeric(9,2) NOT NULL,
    price_reduced numeric(9,2),
    price_free numeric(9,2),
    price_staff numeric(9,2) NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: food_service_menu_items; Type: TABLE;
--

CREATE TABLE food_service_menu_items (
    menu_item_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    menu_id integer NOT NULL,
    item_id integer NOT NULL,
    category_id integer,
    sort_order numeric,
    does_count varchar(1),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: food_service_menus; Type: TABLE;
--

CREATE TABLE food_service_menus (
    menu_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    title varchar(25) NOT NULL,
    sort_order numeric,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: food_service_staff_accounts; Type: TABLE;
--

CREATE TABLE food_service_staff_accounts (
    staff_id integer PRIMARY KEY,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    status varchar(25),
    barcode varchar(50) UNIQUE,
    balance numeric(9,2) NOT NULL,
    transaction_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: food_service_staff_transactions; Type: TABLE;
--

CREATE TABLE food_service_staff_transactions (
    transaction_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    staff_id integer NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    school_id integer NOT NULL,
    syear numeric(4,0) NOT NULL,
    balance numeric(9,2),
    `timestamp` datetime,
    short_name varchar(25),
    description varchar(50),
    seller_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: food_service_staff_transaction_items; Type: TABLE;
--

CREATE TABLE food_service_staff_transaction_items (
    item_id integer NOT NULL,
    transaction_id integer NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES food_service_staff_transactions(transaction_id),
    menu_item_id integer COMMENT 'References food_service_menu_items(menu_item_id)',
    amount numeric(9,2),
    short_name varchar(25),
    description varchar(50),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (item_id, transaction_id)
);


--
-- Name: food_service_student_accounts; Type: TABLE;
--

CREATE TABLE food_service_student_accounts (
    student_id integer PRIMARY KEY,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    account_id integer NOT NULL,
    discount varchar(25),
    status varchar(25),
    barcode varchar(50) UNIQUE,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: food_service_transactions; Type: TABLE;
--

CREATE TABLE food_service_transactions (
    transaction_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    account_id integer NOT NULL,
    student_id integer,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    school_id integer NOT NULL,
    syear numeric(4,0) NOT NULL,
    discount varchar(25),
    balance numeric(9,2),
    `timestamp` datetime,
    short_name varchar(25),
    description varchar(50),
    seller_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: food_service_transaction_items; Type: TABLE;
--

CREATE TABLE food_service_transaction_items (
    item_id integer NOT NULL,
    transaction_id integer NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES food_service_transactions(transaction_id),
    menu_item_id integer COMMENT 'References food_service_menu_items(menu_item_id)',
    amount numeric(9,2),
    discount varchar(25),
    short_name varchar(25),
    description varchar(50),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (item_id, transaction_id)
);


--
-- Name: gradebook_assignment_types; Type: TABLE;
--

CREATE TABLE gradebook_assignment_types (
    assignment_type_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    staff_id integer NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    course_id integer NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    title text NOT NULL,
    final_grade_percent numeric(6,5),
    sort_order numeric,
    color varchar(30),
    created_mp integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: gradebook_assignments; Type: TABLE;
--

CREATE TABLE gradebook_assignments (
    assignment_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    staff_id integer NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    marking_period_id integer NOT NULL,
    FOREIGN KEY (marking_period_id) REFERENCES school_marking_periods(marking_period_id),
    course_period_id integer,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id),
    course_id integer,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    assignment_type_id integer NOT NULL,
    title text NOT NULL,
    assigned_date date,
    due_date date,
    points integer NOT NULL,
    description longtext,
    file text,
    default_points integer,
    submission varchar(1),
    weight integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: gradebook_grades; Type: TABLE;
--

CREATE TABLE gradebook_grades (
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    period_id integer, -- @deprecated since 6.9 SQL gradebook_grades column PERIOD_ID.
    course_period_id integer NOT NULL,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id),
    assignment_id integer NOT NULL,
    points numeric(6,2),
    comment text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (student_id, assignment_id, course_period_id)
);


--
-- Name: grades_completed; Type: TABLE;
--
-- Idea: could be dynamic, like a view?

CREATE TABLE grades_completed (
    staff_id integer NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    marking_period_id integer NOT NULL,
    FOREIGN KEY (marking_period_id) REFERENCES school_marking_periods(marking_period_id),
    course_period_id integer NOT NULL,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (staff_id, marking_period_id, course_period_id)
);


--
-- Name: lunch_period; Type: TABLE;
--

CREATE TABLE lunch_period (
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    school_date date NOT NULL,
    period_id integer NOT NULL,
    attendance_code integer,
    attendance_teacher_code integer,
    attendance_reason varchar(100),
    admin varchar(1),
    course_period_id integer,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id),
    marking_period_id integer,
    FOREIGN KEY (marking_period_id) REFERENCES school_marking_periods(marking_period_id),
    comment varchar(100),
    table_name integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (student_id, school_date, period_id)
);


--
-- Name: history_marking_periods; Type: TABLE;
--

CREATE TABLE history_marking_periods (
    parent_id integer,
    mp_type varchar(20),
    name varchar(50) NOT NULL,
    short_name varchar(10),
    post_end_date date,
    school_id integer NOT NULL,
    syear numeric(4,0),
    marking_period_id integer PRIMARY KEY,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: marking_periods; Type: VIEW;
--

CREATE VIEW marking_periods AS
    SELECT school_marking_periods.marking_period_id, 'Rosario' AS mp_source, school_marking_periods.syear, school_marking_periods.school_id, CASE WHEN school_marking_periods.mp = 'FY' THEN 'year' WHEN school_marking_periods.mp = 'SEM' THEN 'semester' WHEN school_marking_periods.mp = 'QTR' THEN 'quarter' ELSE NULL END AS mp_type, school_marking_periods.title, school_marking_periods.short_name, school_marking_periods.sort_order, CASE WHEN school_marking_periods.parent_id > 0 THEN school_marking_periods.parent_id ELSE -1 END AS parent_id, CASE WHEN ((SELECT smp.parent_id FROM school_marking_periods smp WHERE (smp.marking_period_id = school_marking_periods.parent_id)) > 0) THEN (SELECT smp.parent_id FROM school_marking_periods smp WHERE (smp.marking_period_id = school_marking_periods.parent_id)) ELSE -1 END AS grandparent_id, school_marking_periods.start_date, school_marking_periods.end_date, school_marking_periods.post_start_date, school_marking_periods.post_end_date, school_marking_periods.does_grades, school_marking_periods.does_comments FROM school_marking_periods
    UNION SELECT history_marking_periods.marking_period_id, 'History' AS mp_source, history_marking_periods.syear, history_marking_periods.school_id, history_marking_periods.mp_type, history_marking_periods.name AS title, history_marking_periods.short_name, NULL AS sort_order, history_marking_periods.parent_id, (-1) AS grandparent_id, NULL AS start_date, history_marking_periods.post_end_date AS end_date, NULL AS post_start_date, history_marking_periods.post_end_date, 'Y' AS does_grades, NULL AS does_comments FROM history_marking_periods;


--
-- Name: moodlexrosario; Type: TABLE;
--

CREATE TABLE moodlexrosario (
    `column` varchar(100) NOT NULL,
    rosario_id integer NOT NULL,
    moodle_id integer NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (`column`, rosario_id)
);


--
-- Name: people; Type: TABLE;
--

CREATE TABLE people (
    person_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    last_name varchar(50) NOT NULL,
    first_name varchar(50) NOT NULL,
    middle_name varchar(50),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: people_field_categories; Type: TABLE;
--

CREATE TABLE people_field_categories (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title text NOT NULL,
    sort_order numeric,
    custody char(1),
    emergency char(1),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: people_fields; Type: TABLE;
--

CREATE TABLE people_fields (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type varchar(10),
    title text NOT NULL,
    sort_order numeric,
    select_options text,
    category_id integer,
    required varchar(1),
    default_selection text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: people_join_contacts; Type: TABLE;
--

CREATE TABLE people_join_contacts (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    person_id integer,
    title varchar(100),
    value varchar(100),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: portal_notes; Type: TABLE;
--

CREATE TABLE portal_notes (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    syear numeric(4,0) NOT NULL,
    title text NOT NULL,
    content longtext,
    sort_order numeric,
    published_user integer,
    published_date datetime, -- @deprecated since 11.0 use created_at instead
    start_date date,
    end_date date,
    published_profiles text,
    file_attached text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: portal_poll_questions; Type: TABLE;
--

CREATE TABLE portal_poll_questions (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    portal_poll_id integer NOT NULL,
    question text NOT NULL,
    type varchar(20),
    options text,
    votes text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: portal_polls; Type: TABLE;
--

CREATE TABLE portal_polls (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    syear numeric(4,0) NOT NULL,
    title text NOT NULL,
    votes_number integer,
    display_votes varchar(1),
    sort_order numeric,
    published_user integer,
    published_date datetime, -- @deprecated since 11.0 use created_at instead
    start_date date,
    end_date date,
    published_profiles text,
    students_teacher_id integer,
    excluded_users text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: profile_exceptions; Type: TABLE;
--

CREATE TABLE profile_exceptions (
    profile_id integer NOT NULL,
    modname varchar(150) NOT NULL,
    can_use varchar(1),
    can_edit varchar(1),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (profile_id, modname)
);


--
-- Name: program_config; Type: TABLE;
--

CREATE TABLE program_config (
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    program varchar(100) NOT NULL,
    title varchar(100) NOT NULL,
    value longtext,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: program_user_config; Type: TABLE;
--

CREATE TABLE program_user_config (
    user_id integer NOT NULL,
    program varchar(100) NOT NULL,
    title varchar(100) NOT NULL,
    value longtext,
    school_id integer, -- Can be NULL.
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: report_card_comment_categories; Type: TABLE;
--

CREATE TABLE report_card_comment_categories (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    course_id integer,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    sort_order numeric,
    title text NOT NULL,
    rollover_id integer,
    color varchar(30),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: report_card_comment_code_scales; Type: TABLE;
--

CREATE TABLE report_card_comment_code_scales (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    title varchar(25) NOT NULL,
    comment varchar(100),
    sort_order numeric,
    rollover_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: report_card_comment_codes; Type: TABLE;
--

CREATE TABLE report_card_comment_codes (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    scale_id integer NOT NULL,
    title varchar(5) NOT NULL,
    short_name varchar(100),
    comment varchar(100),
    sort_order numeric,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: report_card_comments; Type: TABLE;
--

CREATE TABLE report_card_comments (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    course_id integer, -- Can be 0, so no REFERENCES courses(course_id).
    category_id integer,
    scale_id integer,
    sort_order numeric,
    title text NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: report_card_grade_scales; Type: TABLE;
--

CREATE TABLE report_card_grade_scales (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    title text NOT NULL,
    comment text,
    hhr_gpa_value numeric(7,2),
    hr_gpa_value numeric(7,2),
    sort_order numeric,
    rollover_id integer,
    gp_scale numeric(7,2) NOT NULL,
    gp_passing_value numeric(7,2) NOT NULL,
    hrs_gpa_value numeric(7,2),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: report_card_grades; Type: TABLE;
--

CREATE TABLE report_card_grades (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    title varchar(5) NOT NULL,
    sort_order numeric,
    gpa_value numeric(7,2),
    break_off numeric(7,2),
    comment text,
    grade_scale_id integer,
    unweighted_gp numeric(7,2),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: resources; Type: TABLE;
--

CREATE TABLE resources (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    title text NOT NULL,
    link text,
    published_profiles text,
    published_grade_levels text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: schedule; Type: TABLE;
--

CREATE TABLE schedule (
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    start_date date NOT NULL,
    end_date date,
    modified_date date, -- @deprecated since 5.0 Use updated_at.
    modified_by varchar(255),
    course_id integer NOT NULL,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    course_period_id integer NOT NULL,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id),
    mp varchar(3),
    marking_period_id integer,
    FOREIGN KEY (marking_period_id) REFERENCES school_marking_periods(marking_period_id),
    scheduler_lock varchar(1),
    id integer, -- Any IDea?
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: schedule_requests; Type: TABLE;
--

CREATE TABLE schedule_requests (
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    request_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    subject_id integer,
    course_id integer,
    FOREIGN KEY (course_id) REFERENCES courses(course_id),
    marking_period_id integer,
    FOREIGN KEY (marking_period_id) REFERENCES school_marking_periods(marking_period_id), -- Not used...
    priority integer,
    with_teacher_id integer,
    not_teacher_id integer,
    with_period_id integer,
    not_period_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: school_fields; Type: TABLE;
--

CREATE TABLE school_fields (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type varchar(10) NOT NULL,
    title text NOT NULL,
    sort_order numeric,
    select_options text,
    required varchar(1),
    default_selection text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: school_gradelevels; Type: TABLE;
--

CREATE TABLE school_gradelevels (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school_id integer NOT NULL,
    short_name varchar(3),
    title varchar(50) NOT NULL,
    next_grade_id integer,
    sort_order numeric,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: school_periods; Type: TABLE;
--

CREATE TABLE school_periods (
    period_id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    sort_order numeric,
    title varchar(100) NOT NULL,
    short_name varchar(10),
    length integer,
    start_time varchar(10),
    end_time varchar(10),
    block varchar(10),
    attendance varchar(1),
    rollover_id integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: staff_exceptions; Type: TABLE;
--

CREATE TABLE staff_exceptions (
    user_id integer NOT NULL,
    FOREIGN KEY (user_id) REFERENCES staff(staff_id),
    modname varchar(150) NOT NULL,
    can_use varchar(1),
    can_edit varchar(1),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (user_id, modname)
);


--
-- Name: staff_field_categories; Type: TABLE;
--

CREATE TABLE staff_field_categories (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title text NOT NULL,
    sort_order numeric,
    columns numeric(4,0),
    include varchar(100),
    admin char(1),
    teacher char(1),
    parent char(1),
    `none` char(1),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: staff_fields; Type: TABLE;
--

CREATE TABLE staff_fields (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    type varchar(10) NOT NULL,
    title text NOT NULL,
    sort_order numeric,
    select_options text,
    category_id integer,
    required varchar(1),
    default_selection text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: student_assignments; Type: TABLE;
--

CREATE TABLE student_assignments (
    assignment_id integer NOT NULL,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    data longtext, -- @since 11.0 Use JSON instead of PHP serialize
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (assignment_id, student_id)
);


--
-- Name: student_eligibility_activities; Type: TABLE;
--

CREATE TABLE student_eligibility_activities (
    syear numeric(4,0),
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    activity_id integer NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: student_enrollment_codes; Type: TABLE;
--

CREATE TABLE student_enrollment_codes (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    title varchar(100) NOT NULL,
    short_name varchar(10),
    type varchar(4),
    default_code varchar(1),
    sort_order numeric,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: student_field_categories; Type: TABLE;
--

CREATE TABLE student_field_categories (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title text NOT NULL,
    sort_order numeric,
    columns numeric(4,0),
    include varchar(100),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: student_medical; Type: TABLE;
--

CREATE TABLE student_medical (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    type varchar(25) NOT NULL,
    medical_date date,
    comments varchar(100),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: student_medical_alerts; Type: TABLE;
--

CREATE TABLE student_medical_alerts (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    title varchar(100) NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: student_medical_visits; Type: TABLE;
--

CREATE TABLE student_medical_visits (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    school_date date NOT NULL,
    time_in varchar(20),
    time_out varchar(20),
    reason varchar(100),
    result varchar(100),
    comments text,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: student_mp_comments; Type: TABLE;
--

CREATE TABLE student_mp_comments (
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    syear numeric(4,0) NOT NULL,
    marking_period_id integer NOT NULL,
    FOREIGN KEY (marking_period_id) REFERENCES school_marking_periods(marking_period_id),
    comment text, -- @since 11.0 Use JSON instead of PHP serialize
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (student_id, syear, marking_period_id)
);


--
-- Name: student_mp_stats; Type: TABLE;
-- Fix Class Rank float comparison issue: do NOT use double precision type (inexact), use numeric(22,16) (exact)
-- @link https://www.rosariosis.org/forum/d/665-le-classement-diff-rent-mais-m-me-moyenne/
--

CREATE TABLE student_mp_stats (
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    marking_period_id integer NOT NULL, -- Can be History, so no REFERENCES school_marking_periods(marking_period_id).
    cum_weighted_factor numeric(22,16),
    cum_unweighted_factor numeric(22,16),
    cum_rank integer,
    mp_rank integer,
    class_size integer,
    sum_weighted_factors numeric(22,16),
    sum_unweighted_factors numeric(22,16),
    count_weighted_factors integer,
    count_unweighted_factors integer,
    grade_level_short varchar(3),
    cr_weighted_factors numeric(22,16),
    cr_unweighted_factors numeric(22,16),
    count_cr_factors integer,
    cum_cr_weighted_factor numeric(22,16),
    cum_cr_unweighted_factor numeric(22,16),
    credit_attempted numeric(22,16),
    credit_earned numeric(22,16),
    gp_credits numeric(22,16),
    cr_credits numeric(22,16),
    comments varchar(75),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (student_id, marking_period_id)
);


--
-- Name: student_report_card_comments; Type: TABLE;
--

CREATE TABLE student_report_card_comments (
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    course_period_id integer NOT NULL,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id),
    report_card_comment_id integer NOT NULL,
    comment varchar(5),
    marking_period_id integer NOT NULL,
    FOREIGN KEY (marking_period_id) REFERENCES school_marking_periods(marking_period_id),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (syear, student_id, course_period_id, marking_period_id, report_card_comment_id),
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: student_report_card_grades; Type: TABLE;
--

CREATE TABLE student_report_card_grades (
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    course_period_id integer,
    FOREIGN KEY (course_period_id) REFERENCES course_periods(course_period_id),
    report_card_grade_id integer,
    report_card_comment_id integer,
    comment text,
    grade_percent numeric(4,1),
    marking_period_id integer NOT NULL, -- EditReportCardGrades.php, so no REFERENCES school_marking_periods(marking_period_id).
    grade_letter varchar(5),
    weighted_gp numeric(7,2),
    unweighted_gp numeric(7,2),
    gp_scale numeric(7,2),
    credit_attempted numeric(22,16),
    credit_earned numeric(22,16),
    credit_category varchar(10),
    course_title text NOT NULL,
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    school text,
    class_rank varchar(1),
    credit_hours numeric(6,2),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
    -- History, so no FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: student_enrollment; Type: TABLE;
--

CREATE TABLE student_enrollment (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    syear numeric(4,0) NOT NULL,
    school_id integer NOT NULL,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    grade_id integer,
    start_date date,
    end_date date,
    enrollment_code integer,
    drop_code integer,
    next_school integer,
    calendar_id integer,
    last_school integer,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    FOREIGN KEY (school_id,syear) REFERENCES schools(id,syear)
);


--
-- Name: enroll_grade; Type: VIEW;
--

CREATE VIEW enroll_grade AS
    SELECT e.id, e.syear, e.school_id, e.student_id, e.start_date, e.end_date, sg.short_name, sg.title FROM student_enrollment e, school_gradelevels sg WHERE (e.grade_id = sg.id);



--
-- Name: students_join_address; Type: TABLE;
--

CREATE TABLE students_join_address (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    address_id integer NOT NULL,
    contact_seq numeric(10,0),
    gets_mail varchar(1),
    primary_residence varchar(1),
    legal_residence varchar(1),
    am_bus varchar(1),
    pm_bus varchar(1),
    mailing varchar(1),
    residence varchar(1),
    bus varchar(1),
    bus_pickup varchar(1),
    bus_dropoff varchar(1),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: students_join_people; Type: TABLE;
--

CREATE TABLE students_join_people (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    person_id integer NOT NULL,
    address_id integer,
    custody varchar(1),
    emergency varchar(1),
    student_relation varchar(100),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Name: students_join_users; Type: TABLE;
--

CREATE TABLE students_join_users (
    student_id integer NOT NULL,
    FOREIGN KEY (student_id) REFERENCES students(student_id),
    staff_id integer NOT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id),
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (student_id, staff_id)
);




--
-- Name: templates; Type: TABLE;
--

CREATE TABLE templates (
    modname varchar(150) NOT NULL,
    staff_id integer NOT NULL, -- Can be 0, no REFERENCES staff(staff_id).
    template longtext,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp,
    PRIMARY KEY (modname, staff_id)
);




--
-- Name: transcript_grades; Type: VIEW;
--
-- Add history grades in Transripts

CREATE VIEW transcript_grades AS
    SELECT mp.syear,mp.school_id,mp.marking_period_id,mp.mp_type,
    mp.short_name,mp.parent_id,mp.grandparent_id,
    (SELECT mp2.end_date
        FROM student_report_card_grades
            JOIN marking_periods mp2
            ON mp2.marking_period_id = student_report_card_grades.marking_period_id
        WHERE student_report_card_grades.student_id = sms.student_id
        AND (student_report_card_grades.marking_period_id = mp.parent_id
            OR student_report_card_grades.marking_period_id = mp.grandparent_id)
        AND student_report_card_grades.course_title = srcg.course_title
        ORDER BY mp2.end_date LIMIT 1) AS parent_end_date,
    mp.end_date,sms.student_id,
    (sms.cum_weighted_factor * COALESCE(schools.reporting_gp_scale, (SELECT reporting_gp_scale FROM schools WHERE mp.school_id = id ORDER BY syear LIMIT 1))) AS cum_weighted_gpa,
    (sms.cum_unweighted_factor * schools.reporting_gp_scale) AS cum_unweighted_gpa,
    sms.cum_rank,sms.mp_rank,sms.class_size,
    ((sms.sum_weighted_factors / sms.count_weighted_factors) * schools.reporting_gp_scale) AS weighted_gpa,
    ((sms.sum_unweighted_factors / sms.count_unweighted_factors) * schools.reporting_gp_scale) AS unweighted_gpa,
    sms.grade_level_short,srcg.comment,srcg.grade_percent,srcg.grade_letter,
    srcg.weighted_gp,srcg.unweighted_gp,srcg.gp_scale,srcg.credit_attempted,
    srcg.credit_earned,srcg.course_title,srcg.school AS school_name,
    schools.reporting_gp_scale AS school_scale,
    ((sms.cr_weighted_factors / sms.count_cr_factors) * schools.reporting_gp_scale) AS cr_weighted_gpa,
    ((sms.cr_unweighted_factors / sms.count_cr_factors) * schools.reporting_gp_scale) AS cr_unweighted_gpa,
    (sms.cum_cr_weighted_factor * schools.reporting_gp_scale) AS cum_cr_weighted_gpa,
    (sms.cum_cr_unweighted_factor * schools.reporting_gp_scale) AS cum_cr_unweighted_gpa,
    srcg.class_rank,sms.comments,
    srcg.credit_hours
    FROM marking_periods mp
        JOIN student_report_card_grades srcg
        ON mp.marking_period_id = srcg.marking_period_id
        JOIN student_mp_stats sms
        ON sms.marking_period_id = mp.marking_period_id
            AND sms.student_id = srcg.student_id
        LEFT OUTER JOIN schools
        ON mp.school_id = schools.id
            AND (mp.mp_source<>'History' AND mp.syear = schools.syear)
                OR (mp.mp_source='History' AND mp.syear=(SELECT syear FROM schools WHERE mp.school_id = id ORDER BY syear LIMIT 1))
    ORDER BY srcg.course_period_id;




--
-- Name: user_profiles; Type: TABLE;
--

CREATE TABLE user_profiles (
    id integer NOT NULL AUTO_INCREMENT PRIMARY KEY,
    profile varchar(30),
    title text NOT NULL,
    created_at timestamp DEFAULT current_timestamp,
    updated_at timestamp NULL ON UPDATE current_timestamp
);


--
-- Data for Name: schools; Type: TABLE DATA;
--

INSERT INTO schools VALUES (2023, NULL, 'Default School', '500 S. Street St.', 'Springfield', 'IL', '62704', NULL, 'Mr. Principal', 'www.rosariosis.org', NULL, NULL, 4, NULL, NULL, NULL);



--
-- Data for Name: students; Type: TABLE DATA;
--

INSERT INTO students VALUES (NULL, 'Student', 'Student', 'S', NULL, 'student', '$6$f03d507b27b8b9ff$WKtYRdFZGNjRKUr4btzq/p90hbKRAyB8HmrZpgpUhbAh.GtOCveXtXt43IaEDZJ31rVUYZ7ID8xPgKkCiRyzZ1', NULL, NULL, 'Male', 'White, Non-Hispanic', 'Bug', NULL, '2015-12-04', 'English', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);


--
-- Data for Name: staff; Type: TABLE DATA;
--

INSERT INTO staff VALUES (2023, NULL, 1, NULL, 'Admin', 'Administrator', 'A', NULL, 'admin', '$6$dc51290a001671c6$97VSmw.Qu9sL6vpctFh62/YIbbR6b3DstJJxPXal2OndrtFszsxmVhdQaV2mJvb6Z38sPACXqDDQ7/uquwadd.', NULL, NULL, 'admin', NULL, ',1,', NULL, NULL, 1, NULL, NULL, NULL);
INSERT INTO staff VALUES (2023, NULL, 1, NULL, 'Teach', 'Teacher', 'T', NULL, 'teacher', '$6$cf0dc4c40d38891f$FqKT6nlTer3ujAf8CcQi6ABIEtlow0Va2p6HYh.M6eGWUfpgLr/pfrSwdIcTlV1LDxLg52puVETGMCYKL3vOo/', NULL, NULL, 'teacher', NULL, ',1,', NULL, NULL, 2, NULL, NULL, NULL);
INSERT INTO staff VALUES (2023, NULL, 1, NULL, 'Parent', 'Parent', 'P', NULL, 'parent', '$6$947c923597601364$Kgbb0Ey3lYTYnqM66VkFRgJVFDW48cBAfNF7t0CVjokL7drcEFId61whqpLrRI1w0q2J2VPfg86Obaf1tG2Ng1', NULL, NULL, 'parent', NULL, NULL, NULL, NULL, 3, NULL, NULL, NULL);


--
-- Data for Name: school_marking_periods; Type: TABLE DATA;
-- Note: keep 06-15 and 06-13 as first and last day of the year!

INSERT INTO school_marking_periods VALUES (NULL, 2023, 'FY', 1, NULL, 'Full Year', 'FY', 1, '2023-06-14', '2024-06-12', NULL, NULL, NULL, NULL, NULL, NULL, NULL);
INSERT INTO school_marking_periods VALUES (NULL, 2023, 'SEM', 1, 1, 'Semester 1', 'S1', 1, '2023-06-14', '2023-12-31', '2023-12-28', '2023-12-31', NULL, NULL, NULL, NULL, NULL);
INSERT INTO school_marking_periods VALUES (NULL, 2023, 'SEM', 1, 1, 'Semester 2', 'S2', 2, '2024-01-01', '2024-06-12', '2024-06-11', '2024-06-12', NULL, NULL, NULL, NULL, NULL);
INSERT INTO school_marking_periods VALUES (NULL, 2023, 'QTR', 1, 2, 'Quarter 1', 'Q1', 1, '2023-06-14', '2023-09-13', '2023-09-11', '2023-09-13', 'Y', 'Y', NULL, NULL, NULL);
INSERT INTO school_marking_periods VALUES (NULL, 2023, 'QTR', 1, 2, 'Quarter 2', 'Q2', 2, '2023-09-14', '2023-12-31', '2023-12-28', '2023-12-31', 'Y', 'Y', NULL, NULL, NULL);
INSERT INTO school_marking_periods VALUES (NULL, 2023, 'QTR', 1, 3, 'Quarter 3', 'Q3', 3, '2024-01-01', '2024-03-14', '2024-03-12', '2024-03-14', 'Y', 'Y', NULL, NULL, NULL);
INSERT INTO school_marking_periods VALUES (NULL, 2023, 'QTR', 1, 3, 'Quarter 4', 'Q4', 4, '2024-03-15', '2024-06-12', '2024-06-11', '2024-06-12', 'Y', 'Y', NULL, NULL, NULL);



--
-- Data for Name: courses; Type: TABLE DATA;
--



--
-- Data for Name: course_periods; Type: TABLE DATA;
--



--
-- Data for Name: accounting_categories; Type: TABLE DATA;
--



--
-- Data for Name: accounting_incomes; Type: TABLE DATA;
--



--
-- Data for Name: accounting_salaries; Type: TABLE DATA;
--



--
-- Data for Name: accounting_payments; Type: TABLE DATA;
--



--
-- Data for Name: address; Type: TABLE DATA;
--

INSERT INTO address VALUES (0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'No Address', NULL, NULL, NULL, NULL);


--
-- Data for Name: address_field_categories; Type: TABLE DATA;
--



--
-- Data for Name: address_fields; Type: TABLE DATA;
--



--
-- Data for Name: attendance_calendar; Type: TABLE DATA;
--



--
-- Data for Name: attendance_calendars; Type: TABLE DATA;
--

INSERT INTO attendance_calendars VALUES (1, 'Main', 2023, NULL, 'Y', NULL, NULL, NULL);


--
-- Data for Name: attendance_code_categories; Type: TABLE DATA;
--



--
-- Data for Name: attendance_codes; Type: TABLE DATA;
--

INSERT INTO attendance_codes VALUES (NULL, 2023, 1, 'Absent', 'A', 'teacher', 'A', NULL, 0, NULL, NULL, NULL);
INSERT INTO attendance_codes VALUES (NULL, 2023, 1, 'Present', 'P', 'teacher', 'P', 'Y', 0, NULL, NULL, NULL);
INSERT INTO attendance_codes VALUES (NULL, 2023, 1, 'Tardy', 'T', 'teacher', 'P', NULL, 0, NULL, NULL, NULL);
INSERT INTO attendance_codes VALUES (NULL, 2023, 1, 'Excused Absence', 'E', 'official', 'A', NULL, 0, NULL, NULL, NULL);


--
-- Data for Name: attendance_completed; Type: TABLE DATA;
--



--
-- Data for Name: attendance_day; Type: TABLE DATA;
--



--
-- Data for Name: attendance_period; Type: TABLE DATA;
--



--
-- Data for Name: billing_fees; Type: TABLE DATA;
--



--
-- Data for Name: billing_payments; Type: TABLE DATA;
--



--
-- Data for Name: calendar_events; Type: TABLE DATA;
--



--
-- Data for Name: config; Type: TABLE DATA;
--

INSERT INTO config VALUES (0, 'LOGIN', 'No', NULL, NULL);
INSERT INTO config VALUES (0, 'VERSION', '11.3.3', NULL, NULL);
INSERT INTO config VALUES (0, 'TITLE', 'Rosario Student Information System', NULL, NULL);
INSERT INTO config VALUES (0, 'NAME', 'RosarioSIS', NULL, NULL);
INSERT INTO config VALUES (0, 'MODULES', 'a:13:{s:12:"School_Setup";b:1;s:8:"Students";b:1;s:5:"Users";b:1;s:10:"Scheduling";b:1;s:6:"Grades";b:1;s:10:"Attendance";b:1;s:11:"Eligibility";b:1;s:10:"Discipline";b:1;s:10:"Accounting";b:1;s:15:"Student_Billing";b:1;s:12:"Food_Service";b:1;s:9:"Resources";b:1;s:6:"Custom";b:1;}', NULL, NULL);
INSERT INTO config VALUES (0, 'PLUGINS', 'a:1:{s:6:"Moodle";b:0;}', NULL, NULL);
INSERT INTO config VALUES (0, 'THEME', 'FlatSIS', NULL, NULL);
INSERT INTO config VALUES (0, 'THEME_FORCE', NULL, NULL, NULL);
INSERT INTO config VALUES (0, 'CREATE_USER_ACCOUNT', NULL, NULL, NULL);
INSERT INTO config VALUES (0, 'CREATE_STUDENT_ACCOUNT', NULL, NULL, NULL);
INSERT INTO config VALUES (0, 'CREATE_STUDENT_ACCOUNT_AUTOMATIC_ACTIVATION', NULL, NULL, NULL);
INSERT INTO config VALUES (0, 'CREATE_STUDENT_ACCOUNT_DEFAULT_SCHOOL', NULL, NULL, NULL);
INSERT INTO config VALUES (0, 'STUDENTS_EMAIL_FIELD', NULL, NULL, NULL);
INSERT INTO config VALUES (0, 'DISPLAY_NAME', 'CONCAT(FIRST_NAME,coalesce(NULLIF(CONCAT('' '',MIDDLE_NAME,'' ''),''  ''),'' ''),LAST_NAME)', NULL, NULL);
INSERT INTO config VALUES (1, 'DISPLAY_NAME', 'CONCAT(FIRST_NAME,coalesce(NULLIF(CONCAT('' '',MIDDLE_NAME,'' ''),''  ''),'' ''),LAST_NAME)', NULL, NULL);
INSERT INTO config VALUES (0, 'LIMIT_EXISTING_CONTACTS_ADDRESSES', NULL, NULL, NULL);
INSERT INTO config VALUES (0, 'FAILED_LOGIN_LIMIT', 30, NULL, NULL);
INSERT INTO config VALUES (0, 'PASSWORD_STRENGTH', '2', NULL, NULL);
INSERT INTO config VALUES (0, 'FORCE_PASSWORD_CHANGE_ON_FIRST_LOGIN', NULL, NULL, NULL);
INSERT INTO config VALUES (0, 'GRADEBOOK_CONFIG_ADMIN_OVERRIDE', NULL, NULL, NULL);
INSERT INTO config VALUES (0, 'REMOVE_ACCESS_USERNAME_PREFIX_ADD', NULL, NULL, NULL);
INSERT INTO config VALUES (1, 'SCHOOL_SYEAR_OVER_2_YEARS', 'Y', NULL, NULL);
INSERT INTO config VALUES (1, 'ATTENDANCE_FULL_DAY_MINUTES', '0', NULL, NULL);
INSERT INTO config VALUES (1, 'STUDENTS_USE_MAILING', NULL, NULL, NULL);
INSERT INTO config VALUES (1, 'CURRENCY', '$', NULL, NULL);
INSERT INTO config VALUES (1, 'DECIMAL_SEPARATOR', '.', NULL, NULL);
INSERT INTO config VALUES (1, 'THOUSANDS_SEPARATOR', ',', NULL, NULL);
INSERT INTO config VALUES (1, 'CLASS_RANK_CALCULATE_MPS', NULL, NULL, NULL);


--
-- Data for Name: course_period_school_periods; Type: TABLE DATA;
--



--
-- Data for Name: course_subjects; Type: TABLE DATA;
--



--
-- Data for Name: custom; Type: TABLE DATA;
--



--
-- Data for Name: custom_fields; Type: TABLE DATA;
--

ALTER TABLE custom_fields AUTO_INCREMENT=200000000; -- Start at 200000000.

INSERT INTO custom_fields VALUES (NULL, 'select', 'Gender', 0, 'Male
Female', 1, NULL, NULL, NULL, NULL);
INSERT INTO custom_fields VALUES (NULL, 'select', 'Ethnicity', 1, 'White, Non-Hispanic
Black, Non-Hispanic
Amer. Indian or Alaskan Native
Asian or Pacific Islander
Hispanic
Other', 1, NULL, NULL, NULL, NULL);
INSERT INTO custom_fields VALUES (NULL, 'text', 'Common Name', 2, NULL, 1, NULL, NULL, NULL, NULL);
INSERT INTO custom_fields VALUES (NULL, 'text', 'Social Security', 3, NULL, 1, NULL, NULL, NULL, NULL);
INSERT INTO custom_fields VALUES (NULL, 'date', 'Birthdate', 4, NULL, 1, NULL, NULL, NULL, NULL);
INSERT INTO custom_fields VALUES (NULL, 'select', 'Language', 5, 'English
Spanish', 1, NULL, NULL, NULL, NULL);
INSERT INTO custom_fields VALUES (NULL, 'text', 'Physician', 6, NULL, 2, NULL, NULL, NULL, NULL);
INSERT INTO custom_fields VALUES (NULL, 'text', 'Physician Phone', 7, NULL, 2, NULL, NULL, NULL, NULL);
INSERT INTO custom_fields VALUES (NULL, 'text', 'Preferred Hospital', 8, NULL, 2, NULL, NULL, NULL, NULL);
INSERT INTO custom_fields VALUES (NULL, 'textarea', 'Comments', 9, NULL, 2, NULL, NULL, NULL, NULL);
INSERT INTO custom_fields VALUES (NULL, 'radio', 'Has Doctor''s Note', 10, NULL, 2, NULL, NULL, NULL, NULL);
INSERT INTO custom_fields VALUES (NULL, 'textarea', 'Doctor''s Note Comments', 11, NULL, 2, NULL, NULL, NULL, NULL);


--
-- Data for Name: discipline_field_usage; Type: TABLE DATA;
--

INSERT INTO discipline_field_usage VALUES (NULL, 3, 2023, 1, 'Parents Contacted by Teacher', '', 4, NULL, NULL);
INSERT INTO discipline_field_usage VALUES (NULL, 4, 2023, 1, 'Parent Contacted by Administrator', '', 5, NULL, NULL);
INSERT INTO discipline_field_usage VALUES (NULL, 6, 2023, 1, 'Comments', '', 6, NULL, NULL);
INSERT INTO discipline_field_usage VALUES (NULL, 1, 2023, 1, 'Violation', 'Skipping Class
Profanity, vulgarity, offensive language
Insubordination (Refusal to Comply, Disrespectful Behavior)
Inebriated (Alcohol or Drugs)
Talking out of Turn
Harassment
Fighting
Public Display of Affection
Other', 1, NULL, NULL);
INSERT INTO discipline_field_usage VALUES (NULL, 2, 2023, 1, 'Detention Assigned', '10 Minutes
20 Minutes
30 Minutes
Discuss Suspension', 2, NULL, NULL);
INSERT INTO discipline_field_usage VALUES (NULL, 5, 2023, 1, 'Suspensions (Office Only)', 'Half Day
In School Suspension
1 Day
2 Days
3 Days
5 Days
7 Days
Expulsion', 3, NULL, NULL);


--
-- Data for Name: discipline_fields; Type: TABLE DATA;
--

INSERT INTO discipline_fields VALUES (NULL, 'Violation', '', 'multiple_checkbox', 'CATEGORY_1', NULL, NULL);
INSERT INTO discipline_fields VALUES (NULL, 'Detention Assigned', '', 'multiple_radio', 'CATEGORY_2', NULL, NULL);
INSERT INTO discipline_fields VALUES (NULL, 'Parents Contacted By Teacher', '', 'checkbox', 'CATEGORY_3', NULL, NULL);
INSERT INTO discipline_fields VALUES (NULL, 'Parent Contacted by Administrator', '', 'text', 'CATEGORY_4', NULL, NULL);
INSERT INTO discipline_fields VALUES (NULL, 'Suspensions (Office Only)', '', 'multiple_checkbox', 'CATEGORY_5', NULL, NULL);
INSERT INTO discipline_fields VALUES (NULL, 'Comments', '', 'textarea', 'CATEGORY_6', NULL, NULL);


--
-- Data for Name: discipline_referrals; Type: TABLE DATA;
--



--
-- Data for Name: eligibility; Type: TABLE DATA;
--



--
-- Data for Name: eligibility_activities; Type: TABLE DATA;
--

INSERT INTO eligibility_activities VALUES (NULL, 2023, 1, 'Boy''s Basketball', '2023-10-01', '2024-04-12', NULL, NULL, NULL);
INSERT INTO eligibility_activities VALUES (NULL, 2023, 1, 'Chess Team', '2023-09-03', '2024-06-05', NULL, NULL, NULL);
INSERT INTO eligibility_activities VALUES (NULL, 2023, 1, 'Girl''s Basketball', '2023-10-01', '2024-04-12', NULL, NULL, NULL);


--
-- Data for Name: eligibility_completed; Type: TABLE DATA;
--



--
-- Data for Name: food_service_accounts; Type: TABLE DATA;
--

INSERT INTO food_service_accounts VALUES (1, 0.00, NULL, NULL, NULL);


--
-- Data for Name: food_service_categories; Type: TABLE DATA;
--

INSERT INTO food_service_categories VALUES (NULL, 1, 1, 'Lunch Items', 1, NULL, NULL);


--
-- Data for Name: food_service_items; Type: TABLE DATA;
--

INSERT INTO food_service_items VALUES (NULL, 1, 'HOTL', 1, 'Student Lunch', 'Lunch.png', 1.65, 0.40, 0.00, 2.35, NULL, NULL);
INSERT INTO food_service_items VALUES (NULL, 1, 'MILK', 2, 'Milk', 'Milk.png', 0.25, NULL, NULL, 0.50, NULL, NULL);
INSERT INTO food_service_items VALUES (NULL, 1, 'XTRA', 3, 'Extra', 'Sandwich.png', 0.50, NULL, NULL, 1.00, NULL, NULL);
INSERT INTO food_service_items VALUES (NULL, 1, 'PIZZA', 4, 'Extra Pizza', 'Pizza.png', 1.00, NULL, NULL, 1.00, NULL, NULL);


--
-- Data for Name: food_service_menu_items; Type: TABLE DATA;
--

INSERT INTO food_service_menu_items VALUES (NULL, 1, 1, 1, 1, NULL, NULL, NULL, NULL);
INSERT INTO food_service_menu_items VALUES (NULL, 1, 1, 2, 1, NULL, NULL, NULL, NULL);
INSERT INTO food_service_menu_items VALUES (NULL, 1, 1, 3, 1, NULL, NULL, NULL, NULL);
INSERT INTO food_service_menu_items VALUES (NULL, 1, 1, 4, 1, NULL, NULL, NULL, NULL);


--
-- Data for Name: food_service_menus; Type: TABLE DATA;
--

INSERT INTO food_service_menus VALUES (NULL, 1, 'Lunch', 1, NULL, NULL);


--
-- Data for Name: food_service_staff_accounts; Type: TABLE DATA;
--



--
-- Data for Name: food_service_staff_transaction_items; Type: TABLE DATA;
--



--
-- Data for Name: food_service_staff_transactions; Type: TABLE DATA;
--



--
-- Data for Name: food_service_student_accounts; Type: TABLE DATA;
--

INSERT INTO food_service_student_accounts VALUES (1, 1, NULL, NULL, '1000001', NULL, NULL);


--
-- Data for Name: food_service_transaction_items; Type: TABLE DATA;
--



--
-- Data for Name: food_service_transactions; Type: TABLE DATA;
--



--
-- Data for Name: gradebook_assignment_types; Type: TABLE DATA;
--



--
-- Data for Name: gradebook_assignments; Type: TABLE DATA;
--



--
-- Data for Name: gradebook_grades; Type: TABLE DATA;
--



--
-- Data for Name: grades_completed; Type: TABLE DATA;
--



--
-- Data for Name: history_marking_periods; Type: TABLE DATA;
--



--
-- Data for Name: lunch_period; Type: TABLE DATA;
--



--
-- Data for Name: moodlexrosario; Type: TABLE DATA;
--

INSERT INTO moodlexrosario VALUES ('staff_id', 1, 2, NULL, NULL);


--
-- Data for Name: people; Type: TABLE DATA;
--



--
-- Data for Name: people_field_categories; Type: TABLE DATA;
--



--
-- Data for Name: people_fields; Type: TABLE DATA;
--



--
-- Data for Name: people_join_contacts; Type: TABLE DATA;
--



--
-- Data for Name: portal_notes; Type: TABLE DATA;
--



--
-- Data for Name: portal_poll_questions; Type: TABLE DATA;
--



--
-- Data for Name: portal_polls; Type: TABLE DATA;
--



--
-- Data for Name: profile_exceptions; Type: TABLE DATA;
--

INSERT INTO profile_exceptions VALUES (1, 'School_Setup/PortalNotes.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/Schools.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/CopySchool.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/SchoolFields.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/MarkingPeriods.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/Calendar.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/Periods.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/GradeLevels.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/Rollover.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/Student.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/Student.php&include=General_Info&student_id=new', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/AssignOtherInfo.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/AddUsers.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/AdvancedReport.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/AddDrop.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/Letters.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/StudentLabels.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/PrintStudentInfo.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/StudentFields.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/EnrollmentCodes.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/Student.php&category_id=1', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/Student.php&category_id=3', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/Student.php&category_id=2', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/User.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/User.php&staff_id=new', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/AddStudents.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/Preferences.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/Profiles.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/Exceptions.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/UserFields.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/TeacherPrograms.php&include=Eligibility/EnterEligibility.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/User.php&category_id=1', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/User.php&category_id=1&user_profile', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/User.php&category_id=1&schools', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/User.php&category_id=2', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/User.php&category_id=3', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/Schedule.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/Requests.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/MassSchedule.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/MassRequests.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/MassDrops.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/PrintSchedules.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/PrintClassLists.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/PrintClassPictures.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/PrintRequests.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/ScheduleReport.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/RequestsReport.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/IncompleteSchedules.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/AddDrop.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/Courses.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/Scheduler.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/ReportCards.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/HonorRoll.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/FixGPA.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/Transcripts.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/StudentGrades.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/ProgressReports.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/TeacherCompletion.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/GradeBreakdown.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/FinalGrades.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/Configuration.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/GPARankList.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/ReportCardGrades.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/ReportCardComments.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/ReportCardCommentCodes.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/EditHistoryMarkingPeriods.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/EditReportCardGrades.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Grades/MassCreateAssignments.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/TeacherPrograms.php&include=Grades/InputFinalGrades.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/TeacherPrograms.php&include=Grades/Grades.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/TeacherPrograms.php&include=Grades/AnomalousGrades.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Attendance/Administration.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Attendance/AddAbsences.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Attendance/TeacherCompletion.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Attendance/Percent.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Attendance/DailySummary.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Attendance/FixDailyAttendance.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Attendance/DuplicateAttendance.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Attendance/AttendanceCodes.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Users/TeacherPrograms.php&include=Attendance/TakeAttendance.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Eligibility/Student.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Eligibility/AddActivity.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Eligibility/StudentList.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Eligibility/TeacherCompletion.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Eligibility/Activities.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Eligibility/EntryTimes.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/Accounts.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/Statements.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/Transactions.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/ServeMenus.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/ActivityReport.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/TransactionsReport.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/MenuReports.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/Reminders.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/DailyMenus.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/MenuItems.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/Menus.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/Kiosk.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Resources/Resources.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Accounting/DailyTransactions.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Accounting/Expenses.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Accounting/Incomes.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Accounting/Salaries.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Accounting/StaffBalances.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Accounting/StaffPayments.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Accounting/Statements.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Accounting/Categories.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'School_Setup/Schools.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'School_Setup/MarkingPeriods.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'School_Setup/Calendar.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/Student.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/AddUsers.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/AdvancedReport.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/StudentLabels.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/Letters.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/Student.php&category_id=1', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/Student.php&category_id=3', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/Student.php&category_id=4', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Users/User.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Users/Preferences.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Users/User.php&category_id=1', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Users/User.php&category_id=2', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Users/User.php&category_id=3', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Scheduling/Schedule.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Scheduling/Courses.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Scheduling/PrintSchedules.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Scheduling/PrintClassLists.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Scheduling/PrintClassPictures.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/InputFinalGrades.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/ReportCards.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/Grades.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/Assignments.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/Assignments-new.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/AnomalousGrades.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/ProgressReports.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/StudentGrades.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/FinalGrades.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/Configuration.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/ReportCardGrades.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/ReportCardComments.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/ReportCardCommentCodes.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Attendance/TakeAttendance.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Attendance/DailySummary.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Eligibility/EnterEligibility.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Food_Service/Accounts.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Food_Service/Statements.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Food_Service/DailyMenus.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Food_Service/MenuItems.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Resources/Resources.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Accounting/Salaries.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Accounting/StaffPayments.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Accounting/Statements.php&_ROSARIO_PDF', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'School_Setup/Schools.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'School_Setup/MarkingPeriods.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'School_Setup/Calendar.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Students/Student.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Students/Student.php&category_id=1', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Students/Student.php&category_id=3', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Users/User.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Users/Preferences.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Users/User.php&category_id=1', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Users/User.php&category_id=2', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Users/User.php&category_id=3', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Scheduling/Schedule.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Scheduling/Courses.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Scheduling/PrintSchedules.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Scheduling/PrintClassPictures.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Scheduling/Requests.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Grades/StudentGrades.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Grades/StudentAssignments.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Grades/FinalGrades.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Grades/ReportCards.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Grades/ProgressReports.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Grades/Transcripts.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Grades/GPARankList.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Attendance/DailySummary.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Eligibility/Student.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Eligibility/StudentList.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Food_Service/Accounts.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Food_Service/Statements.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Food_Service/DailyMenus.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Food_Service/MenuItems.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Resources/Resources.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'School_Setup/Schools.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'School_Setup/MarkingPeriods.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'School_Setup/Calendar.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Students/Student.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Students/Student.php&category_id=1', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Students/Student.php&category_id=3', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Scheduling/Schedule.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Scheduling/Courses.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Scheduling/PrintSchedules.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Scheduling/PrintClassPictures.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Scheduling/Requests.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Grades/StudentGrades.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Grades/StudentAssignments.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Grades/FinalGrades.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Grades/ReportCards.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Grades/ProgressReports.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Grades/Transcripts.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Grades/GPARankList.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Attendance/StudentSummary.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Attendance/DailySummary.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Eligibility/Student.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Eligibility/StudentList.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Food_Service/Accounts.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Food_Service/Statements.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Food_Service/DailyMenus.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Food_Service/MenuItems.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Resources/Resources.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Users/Preferences.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Custom/MyReport.php', NULL, NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Custom/CreateParents.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Custom/NotifyParents.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Custom/RemoveAccess.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Custom/AttendanceSummary.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Custom/Registration.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Custom/Registration.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Custom/Registration.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Discipline/MakeReferral.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Discipline/Referrals.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Discipline/CategoryBreakdown.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Discipline/CategoryBreakdownTime.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Discipline/StudentFieldBreakdown.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Discipline/ReferralLog.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Discipline/DisciplineForm.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Discipline/ReferralForm.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Discipline/MakeReferral.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Discipline/Referrals.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/GradebookBreakdown.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/DatabaseBackup.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/PortalPolls.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/Configuration.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/AccessLog.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/StudentFees.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/StudentPayments.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/StudentPayments.php&modfunc=remove', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/MassAssignFees.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/MassAssignPayments.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/StudentBalances.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/DailyTransactions.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/Statements.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/Fees.php', 'Y', 'Y', NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Student_Billing/StudentFees.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Student_Billing/StudentPayments.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Student_Billing/DailyTransactions.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (0, 'Student_Billing/Statements.php&_ROSARIO_PDF', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Student_Billing/StudentFees.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Student_Billing/StudentPayments.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Student_Billing/DailyTransactions.php', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (3, 'Student_Billing/Statements.php&_ROSARIO_PDF', 'Y', NULL, NULL, NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/StudentBreakdown.php', 'Y', 'Y', NULL, NULL);


--
-- Data for Name: program_config; Type: TABLE DATA;
--

INSERT INTO program_config VALUES (2023, 1, 'eligibility', 'START_DAY', '1', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'eligibility', 'START_HOUR', '23', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'eligibility', 'START_MINUTE', '30', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'eligibility', 'START_M', 'PM', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'eligibility', 'END_DAY', '5', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'eligibility', 'END_HOUR', '23', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'eligibility', 'END_MINUTE', '30', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'eligibility', 'END_M', 'PM', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'attendance', 'ATTENDANCE_EDIT_DAYS_BEFORE', NULL, NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'attendance', 'ATTENDANCE_EDIT_DAYS_AFTER', NULL, NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'grades', 'GRADES_DOES_LETTER_PERCENT', '0', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'grades', 'GRADES_HIDE_NON_ATTENDANCE_COMMENT', NULL, NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'grades', 'GRADES_TEACHER_ALLOW_EDIT', NULL, NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'grades', 'GRADES_GRADEBOOK_TEACHER_ALLOW_EDIT', 'Y', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'grades', 'GRADES_DO_STATS_STUDENTS_PARENTS', NULL, NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'grades', 'GRADES_DO_STATS_ADMIN_TEACHERS', 'Y', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'students', 'STUDENTS_USE_BUS', 'Y', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'students', 'STUDENTS_USE_CONTACT', 'Y', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'students', 'STUDENTS_SEMESTER_COMMENTS', NULL, NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'moodle', 'MOODLE_URL', NULL, NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'moodle', 'MOODLE_TOKEN', NULL, NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'moodle', 'MOODLE_PARENT_ROLE_ID', NULL, NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'food_service', 'FOOD_SERVICE_BALANCE_WARNING', '5', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'food_service', 'FOOD_SERVICE_BALANCE_MINIMUM', '-40', NULL, NULL);
INSERT INTO program_config VALUES (2023, 1, 'food_service', 'FOOD_SERVICE_BALANCE_TARGET', '19', NULL, NULL);


--
-- Data for Name: program_user_config; Type: TABLE DATA;
--



--
-- Data for Name: report_card_comment_categories; Type: TABLE DATA;
--



--
-- Data for Name: report_card_comment_code_scales; Type: TABLE DATA;
--



--
-- Data for Name: report_card_comment_codes; Type: TABLE DATA;
--



--
-- Data for Name: report_card_comments; Type: TABLE DATA;
--

INSERT INTO report_card_comments VALUES (NULL, 2023, 1, NULL, NULL, NULL, 1, '^n Fails to Meet Course Requirements', NULL, NULL);
INSERT INTO report_card_comments VALUES (NULL, 2023, 1, NULL, NULL, NULL, 2, '^n Comes to ^s Class Unprepared', NULL, NULL);
INSERT INTO report_card_comments VALUES (NULL, 2023, 1, NULL, NULL, NULL, 3, '^n Exerts Positive Influence in Class', NULL, NULL);


--
-- Data for Name: report_card_grade_scales; Type: TABLE DATA;
--

INSERT INTO report_card_grade_scales VALUES (NULL, 2023, 1, 'Main', NULL, NULL, NULL, 1, NULL, 4, 0, NULL, NULL, NULL);


--
-- Data for Name: report_card_grades; Type: TABLE DATA;
--

INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'A+', 1, 4.00, 97, 'Consistently superior', 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'A', 2, 4.00, 93, 'Superior', 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'A-', 3, 3.75, 90, NULL, 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'B+', 4, 3.50, 87, NULL, 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'B', 5, 3.00, 83, 'Above average', 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'B-', 6, 2.75, 80, NULL, 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'C+', 7, 2.50, 77, NULL, 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'C', 8, 2.00, 73, 'Average', 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'C-', 9, 1.75, 70, NULL, 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'D+', 10, 1.50, 67, NULL, 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'D', 11, 1.00, 63, 'Below average', 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'D-', 12, 0.75, 60, NULL, 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'F', 13, 0.00, 0, 'Failing', 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'I', 14, 0.00, 0, 'Incomplete', 1, NULL, NULL, NULL);
INSERT INTO report_card_grades VALUES (NULL, 2023, 1, 'N/A', 15, NULL, NULL, NULL, 1, NULL, NULL, NULL);


--
-- Data for Name: resources; Type: TABLE DATA;
--

INSERT INTO resources VALUES (NULL, 1, 'Print Handbook', 'Help.php', NULL, NULL, NULL, NULL);
INSERT INTO resources VALUES (NULL, 1, 'Quick Setup Guide', 'https://www.rosariosis.org/quick-setup-guide/', NULL, NULL, NULL, NULL);
INSERT INTO resources VALUES (NULL, 1, 'Forum', 'https://www.rosariosis.org/forum/', NULL, NULL, NULL, NULL);
INSERT INTO resources VALUES (NULL, 1, 'Contribute', 'https://www.rosariosis.org/contribute/', NULL, NULL, NULL, NULL);


--
-- Data for Name: schedule; Type: TABLE DATA;
--



--
-- Data for Name: schedule_requests; Type: TABLE DATA;
--



--
-- Data for Name: school_gradelevels; Type: TABLE DATA;
--

INSERT INTO school_gradelevels VALUES (NULL, 1, 'KG', 'Kindergarten', 2, 1, NULL, NULL);
INSERT INTO school_gradelevels VALUES (NULL, 1, '01', '1st', 3, 2, NULL, NULL);
INSERT INTO school_gradelevels VALUES (NULL, 1, '02', '2nd', 4, 3, NULL, NULL);
INSERT INTO school_gradelevels VALUES (NULL, 1, '03', '3rd', 5, 4, NULL, NULL);
INSERT INTO school_gradelevels VALUES (NULL, 1, '04', '4th', 6, 5, NULL, NULL);
INSERT INTO school_gradelevels VALUES (NULL, 1, '05', '5th', 7, 6, NULL, NULL);
INSERT INTO school_gradelevels VALUES (NULL, 1, '06', '6th', 8, 7, NULL, NULL);
INSERT INTO school_gradelevels VALUES (NULL, 1, '07', '7th', 9, 8, NULL, NULL);
INSERT INTO school_gradelevels VALUES (NULL, 1, '08', '8th', NULL, 9, NULL, NULL);


--
-- Data for Name: school_periods; Type: TABLE DATA;
--

INSERT INTO school_periods VALUES (NULL, 2023, 1, 1, 'Full Day', 'FD', 300, NULL, NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO school_periods VALUES (NULL, 2023, 1, 2, 'Half Day AM', 'AM', 150, NULL, NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO school_periods VALUES (NULL, 2023, 1, 3, 'Half Day PM', 'PM', 150, NULL, NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO school_periods VALUES (NULL, 2023, 1, 4, 'Period 1', '01', 50, NULL, NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO school_periods VALUES (NULL, 2023, 1, 5, 'Period 2', '02', 50, NULL, NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO school_periods VALUES (NULL, 2023, 1, 6, 'Period 3', '03', 50, NULL, NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO school_periods VALUES (NULL, 2023, 1, 7, 'Period 4', '04', 50, NULL, NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO school_periods VALUES (NULL, 2023, 1, 8, 'Period 5', '05', 50, NULL, NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO school_periods VALUES (NULL, 2023, 1, 9, 'Period 6', '06', 50, NULL, NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO school_periods VALUES (NULL, 2023, 1, 10, 'Period 7', '07', 50, NULL, NULL, NULL, 'Y', NULL, NULL, NULL);
INSERT INTO school_periods VALUES (NULL, 2023, 1, 11, 'Period 8', '08', 50, NULL, NULL, NULL, 'Y', NULL, NULL, NULL);


--
-- Data for Name: staff_exceptions; Type: TABLE DATA;
--



--
-- Data for Name: staff_field_categories; Type: TABLE DATA;
--

INSERT INTO staff_field_categories VALUES (NULL, 'General Info', 1, NULL, NULL, 'Y', 'Y', 'Y', 'Y', NULL, NULL);
INSERT INTO staff_field_categories VALUES (NULL, 'Schedule', 2, NULL, NULL, NULL, 'Y', NULL, NULL, NULL, NULL);
INSERT INTO staff_field_categories VALUES (NULL, 'Food Service', 3, NULL, 'Food_Service/User', 'Y', 'Y', NULL, NULL, NULL, NULL);


--
-- Data for Name: staff_fields; Type: TABLE DATA;
--

ALTER TABLE staff_fields AUTO_INCREMENT=200000000; -- Start at 200000000.

INSERT INTO staff_fields VALUES (NULL, 'text', 'Email Address', 0, NULL, 1, NULL, NULL, NULL, NULL);
INSERT INTO staff_fields VALUES (NULL, 'text', 'Phone Number', 1, NULL, 1, NULL, NULL, NULL, NULL);


--
-- Data for Name: student_eligibility_activities; Type: TABLE DATA;
--


--
-- Data for Name: student_enrollment_codes; Type: TABLE DATA;
--

INSERT INTO student_enrollment_codes VALUES (NULL, 2023, 'Moved from District', 'MOVE', 'Drop', NULL, 1, NULL, NULL);
INSERT INTO student_enrollment_codes VALUES (NULL, 2023, 'Expelled', 'EXP', 'Drop', NULL, 2, NULL, NULL);
INSERT INTO student_enrollment_codes VALUES (NULL, 2023, 'Beginning of Year', 'EBY', 'Add', 'Y', 3, NULL, NULL);
INSERT INTO student_enrollment_codes VALUES (NULL, 2023, 'From Other District', 'OTHER', 'Add', NULL, 4, NULL, NULL);
INSERT INTO student_enrollment_codes VALUES (NULL, 2023, 'Transferred in District', 'TRAN', 'Drop', NULL, 5, NULL, NULL);
INSERT INTO student_enrollment_codes VALUES (NULL, 2023, 'Transferred in District', 'EMY', 'Add', NULL, 6, NULL, NULL);


--
-- Data for Name: student_field_categories; Type: TABLE DATA;
--

INSERT INTO student_field_categories VALUES (NULL, 'General Info', 1, NULL, NULL, NULL, NULL);
INSERT INTO student_field_categories VALUES (NULL, 'Medical', 3, NULL, NULL, NULL, NULL);
INSERT INTO student_field_categories VALUES (NULL, 'Addresses & Contacts', 2, NULL, NULL, NULL, NULL);
INSERT INTO student_field_categories VALUES (NULL, 'Comments', 4, NULL, NULL, NULL, NULL);
INSERT INTO student_field_categories VALUES (NULL, 'Food Service', 5, NULL, 'Food_Service/Student', NULL, NULL);


--
-- Data for Name: student_medical; Type: TABLE DATA;
--



--
-- Data for Name: student_medical_alerts; Type: TABLE DATA;
--



--
-- Data for Name: student_medical_visits; Type: TABLE DATA;
--



--
-- Data for Name: student_mp_comments; Type: TABLE DATA;
--



--
-- Data for Name: student_mp_stats; Type: TABLE DATA;
--



--
-- Data for Name: student_report_card_comments; Type: TABLE DATA;
--



--
-- Data for Name: student_report_card_grades; Type: TABLE DATA;
--


--
-- Data for Name: student_enrollment; Type: TABLE DATA;
--

INSERT INTO student_enrollment VALUES (NULL, 2023, 1, 1, 7, '2023-06-09', NULL, 3, NULL, 1, 1, 1, NULL, NULL);



--
-- Data for Name: students_join_address; Type: TABLE DATA;
--



--
-- Data for Name: students_join_people; Type: TABLE DATA;
--



--
-- Data for Name: students_join_users; Type: TABLE DATA;
--

INSERT INTO students_join_users VALUES (1, 3, NULL, NULL);


--
-- Data for Name: templates; Type: TABLE DATA;
--

INSERT INTO templates VALUES ('Students/Letters.php', 0, '<p></p>', NULL, NULL);
INSERT INTO templates VALUES ('Grades/HonorRoll.php', 0, '<br /><br /><br />
<div style="text-align: center;"><span style="font-size: xx-large;"><strong>__SCHOOL_ID__</strong><br /></span><br /><span style="font-size: xx-large;">We hereby recognize<br /><br /></span></div>
<div style="text-align: center;"><span style="font-size: xx-large;"><strong>__FIRST_NAME__ __LAST_NAME__</strong><br /><br /></span></div>
<div style="text-align: center;"><span style="font-size: xx-large;">Who has completed all the academic requirements for <br />Honor Roll</span></div>', NULL, NULL);
INSERT INTO templates VALUES ('Grades/Transcripts.php', 0, '<h2 style="text-align: center;">Studies Certificate</h2>
<p>The Principal here undersigned certifies:</p>
<p>That __FIRST_NAME__ __LAST_NAME__ attended at this school the following courses corresponding to grade __GRADE_ID__ in year __YEAR__ with the following grades and credit hours.</p>
<p>__BLOCK2__</p>
<p>&nbsp;</p>
<table style="border-collapse: collapse; width: 100%;" border="0" cellpadding="10"><tbody><tr>
<td style="width: 50%; text-align: center;"><hr />
<p>Signature</p>
<p>&nbsp;</p><hr />
<p>Title</p></td>
<td style="width: 50%; text-align: center;"><hr />
<p>Signature</p>
<p>&nbsp;</p><hr />
<p>Title</p></td></tr></tbody></table>', NULL, NULL);
INSERT INTO templates VALUES ('Custom/CreateParents.php', 0, 'Dear __PARENT_NAME__,

A parent account for the __SCHOOL_ID__ has been created to access school information and student information for the following students:
__ASSOCIATED_STUDENTS__

Your account credentials are:
Username: __USERNAME__
Password: __PASSWORD__

A link to the SIS website and instructions for access are available on the school''s website__BLOCK2__Dear __PARENT_NAME__,

The following students have been added to your parent account on the SIS:
__ASSOCIATED_STUDENTS__', NULL, NULL);
INSERT INTO templates VALUES ('Custom/NotifyParents.php', 0, 'Dear __PARENT_NAME__,

A parent account for the __SCHOOL_ID__ has been created to access school information and student information for the following students:
__ASSOCIATED_STUDENTS__

Your account credentials are:
Username: __USERNAME__
Password: __PASSWORD__

A link to the SIS website and instructions for access are available on the school''s website', NULL, NULL);

--
-- Data for Name: user_profiles; Type: TABLE DATA;
--

INSERT INTO user_profiles VALUES (0, 'student', 'Student', NULL, NULL);
INSERT INTO user_profiles VALUES (NULL, 'admin', 'Administrator', NULL, NULL);
INSERT INTO user_profiles VALUES (NULL, 'teacher', 'Teacher', NULL, NULL);
INSERT INTO user_profiles VALUES (NULL, 'parent', 'Parent', NULL, NULL);


--
-- Note: Unlike PostgreSQL, MySQL automatically creates index for FOREIGN KEY
--

--
-- Name: accounting_payments_ind1; Type: INDEX;
--

CREATE INDEX accounting_payments_ind1 ON accounting_payments (staff_id);


--
-- Name: accounting_payments_ind2; Type: INDEX;
--

CREATE INDEX accounting_payments_ind2 ON accounting_payments (amount);


--
-- Name: address_3; Type: INDEX;
--

CREATE INDEX address_3 ON address (zipcode);


--
-- Name: address_4; Type: INDEX;
--

CREATE INDEX address_4 ON address (street);


--
-- Name: address_desc_ind2; Type: INDEX;
--

CREATE INDEX address_desc_ind2 ON address_fields (type);


--
-- Name: address_fields_ind3; Type: INDEX;
--

CREATE INDEX address_fields_ind3 ON address_fields (category_id);


--
-- Name: attendance_codes_ind3; Type: INDEX;
--

CREATE INDEX attendance_codes_ind3 ON attendance_codes (short_name);


--
-- Name: attendance_period_ind1; Type: INDEX;
--

CREATE INDEX attendance_period_ind1 ON attendance_period (student_id);


--
-- Name: attendance_period_ind2; Type: INDEX;
--

CREATE INDEX attendance_period_ind2 ON attendance_period (period_id);


--
-- Name: attendance_period_ind4; Type: INDEX;
--

CREATE INDEX attendance_period_ind4 ON attendance_period (school_date);


--
-- Name: attendance_period_ind5; Type: INDEX;
--

CREATE INDEX attendance_period_ind5 ON attendance_period (attendance_code);


--
-- Name: billing_payments_ind2; Type: INDEX;
--

CREATE INDEX billing_payments_ind2 ON billing_payments (amount);


--
-- Name: billing_payments_ind3; Type: INDEX;
--

CREATE INDEX billing_payments_ind3 ON billing_payments (refunded_payment_id);


--
-- Name: courses_ind2; Type: INDEX;
--

CREATE INDEX courses_ind2 ON courses (subject_id);


--
-- Name: custom_desc_ind2; Type: INDEX;
--

CREATE INDEX custom_desc_ind2 ON custom_fields (type);


--
-- Name: custom_fields_ind3; Type: INDEX;
--

CREATE INDEX custom_fields_ind3 ON custom_fields (category_id);


--
-- Name: eligibility_ind1; Type: INDEX;
--

CREATE INDEX eligibility_ind1 ON eligibility (student_id, course_period_id, school_date);


--
-- Name: food_service_categories_title; Type: INDEX;
--

CREATE UNIQUE INDEX food_service_categories_title ON food_service_categories (school_id, menu_id, title);


--
-- Name: food_service_items_short_name; Type: INDEX;
--

CREATE UNIQUE INDEX food_service_items_short_name ON food_service_items (school_id, short_name);


--
-- Name: food_service_menus_title; Type: INDEX;
--

CREATE UNIQUE INDEX food_service_menus_title ON food_service_menus (school_id, title);


--
-- Name: gradebook_assignments_ind3; Type: INDEX;
--

CREATE INDEX gradebook_assignments_ind3 ON gradebook_assignments (assignment_type_id);


--
-- Name: gradebook_grades_ind1; Type: INDEX;
--

CREATE INDEX gradebook_grades_ind1 ON gradebook_grades (assignment_id);


--
-- Name: history_marking_period_ind1; Type: INDEX;
--

CREATE INDEX history_marking_period_ind1 ON history_marking_periods (school_id);


--
-- Name: history_marking_period_ind2; Type: INDEX;
--

CREATE INDEX history_marking_period_ind2 ON history_marking_periods (syear);


--
-- Name: lunch_period_ind2; Type: INDEX;
--

CREATE INDEX lunch_period_ind2 ON lunch_period (period_id);


--
-- Name: lunch_period_ind3; Type: INDEX;
--

CREATE INDEX lunch_period_ind3 ON lunch_period (attendance_code);


--
-- Name: lunch_period_ind4; Type: INDEX;
--

CREATE INDEX lunch_period_ind4 ON lunch_period (school_date);


--
-- Name: name; Type: INDEX;
--

CREATE INDEX name ON students (last_name, first_name, middle_name);


--
-- Name: people_1; Type: INDEX;
--

CREATE INDEX people_1 ON people (last_name, first_name);


--
-- Name: people_desc_ind2; Type: INDEX;
--

CREATE INDEX people_desc_ind2 ON people_fields (type);


--
-- Name: people_fields_ind3; Type: INDEX;
--

CREATE INDEX people_fields_ind3 ON people_fields (category_id);


--
-- Name: people_join_contacts_ind1; Type: INDEX;
--

CREATE INDEX people_join_contacts_ind1 ON people_join_contacts (person_id);


--
-- Name: program_user_config_ind1; Type: INDEX;
--

CREATE INDEX program_user_config_ind1 ON program_user_config (user_id, program);


--
-- Name: relations_meets_2; Type: INDEX;
--

CREATE INDEX relations_meets_2 ON students_join_people (address_id);


--
-- Name: report_card_comment_codes_ind1; Type: INDEX;
--

CREATE INDEX report_card_comment_codes_ind1 ON report_card_comment_codes (school_id);


--
-- Name: schedule_ind3; Type: INDEX;
--

CREATE INDEX schedule_ind3 ON schedule (student_id, marking_period_id, start_date, end_date);


--
-- Name: schedule_requests_ind1; Type: INDEX;
--

CREATE INDEX schedule_requests_ind1 ON schedule_requests (student_id, course_id, syear);


--
-- Name: school_desc_ind2; Type: INDEX;
--

CREATE INDEX school_desc_ind2 ON school_fields (type);


--
-- Name: school_gradelevels_ind1; Type: INDEX;
--

CREATE INDEX school_gradelevels_ind1 ON school_gradelevels (school_id);


--
-- Name: school_marking_periods_ind1; Type: INDEX;
--

CREATE INDEX school_marking_periods_ind1 ON school_marking_periods (parent_id);


--
-- Name: school_marking_periods_ind2; Type: INDEX;
--

CREATE INDEX school_marking_periods_ind2 ON school_marking_periods (syear, school_id, start_date, end_date);


--
-- Name: schools_ind1; Type: INDEX;
--

CREATE INDEX schools_ind1 ON schools (syear);


--
-- Name: staff_desc_ind2; Type: INDEX;
--

CREATE INDEX staff_desc_ind2 ON staff_fields (type);


--
-- Name: staff_fields_ind3; Type: INDEX;
--

CREATE INDEX staff_fields_ind3 ON staff_fields (category_id);


--
-- Name: staff_ind1; Type: INDEX;
--

CREATE INDEX staff_ind1 ON staff (staff_id, syear);


--
-- Name: staff_ind2; Type: INDEX;
--

CREATE INDEX staff_ind2 ON staff (last_name, first_name);


--
-- Name: staff_ind3; Type: INDEX;
--

CREATE INDEX staff_ind3 ON staff (schools);


--
-- Name: staff_ind4; Type: INDEX;
--

CREATE UNIQUE INDEX staff_ind4 ON staff (username, syear);


--
-- Name: stu_addr_meets_2; Type: INDEX;
--

CREATE INDEX stu_addr_meets_2 ON students_join_address (address_id);


--
-- Name: student_enrollment_2; Type: INDEX;
--

CREATE INDEX student_enrollment_2 ON student_enrollment (grade_id);


--
-- Name: student_enrollment_4; Type: INDEX;
--

CREATE INDEX student_enrollment_4 ON student_enrollment (start_date, end_date);


--
-- Name: student_report_card_grades_ind4; Type: INDEX;
--

CREATE INDEX student_report_card_grades_ind4 ON student_report_card_grades (marking_period_id);


--
-- Name: students_join_address_ind1; Type: INDEX;
--

CREATE INDEX students_join_address_ind1 ON students_join_address (student_id);


--
-- Name: srcg_mp_stats_update; Type: TRIGGER;
--

CREATE TRIGGER srcg_mp_stats_update AFTER UPDATE ON student_report_card_grades FOR EACH ROW CALL t_update_mp_stats(NEW.student_id, NEW.marking_period_id);


--
-- Name: srcg_mp_stats_insert; Type: TRIGGER;
--

CREATE TRIGGER srcg_mp_stats_insert AFTER INSERT ON student_report_card_grades FOR EACH ROW CALL t_update_mp_stats(NEW.student_id, NEW.marking_period_id);


--
-- Name: srcg_mp_stats_delete; Type: TRIGGER;
--

CREATE TRIGGER srcg_mp_stats_delete AFTER DELETE ON student_report_card_grades FOR EACH ROW CALL t_update_mp_stats(OLD.student_id, OLD.marking_period_id);
