--
-- PostgreSQL database dump
--

SET client_encoding = 'UTF8';
SET check_function_bodies = false;
SET client_min_messages = warning;

--modif Francois: fix calc_cum_cr_gpa()
--
-- Name: calc_cum_cr_gpa(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION calc_cum_cr_gpa(character varying, integer) RETURNS integer
    LANGUAGE plpgsql
    AS $_$DECLARE
  mp_id ALIAS for $1;
  s_id ALIAS for $2;
  mpinfo marking_periods%ROWTYPE;
  s student_mp_stats%ROWTYPE;
BEGIN
    UPDATE student_mp_stats
    SET cum_cr_weighted_factor = cr_weighted_factors/cr_credits,
        cum_cr_unweighted_factor = cr_unweighted_factors/cr_credits
	WHERE student_mp_stats.student_id = s_id and cast(student_mp_stats.marking_period_id as text) = mp_id;
  RETURN 1;
END;
$_$;


--modif Francois: fix calc_cum_gpa()
--
-- Name: calc_cum_gpa(character varying, integer); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION calc_cum_gpa(character varying, integer) RETURNS integer
    LANGUAGE plpgsql
    AS $_$DECLARE
  mp_id ALIAS for $1;
  s_id ALIAS for $2;
  mpinfo marking_periods%ROWTYPE;
  s student_mp_stats%ROWTYPE;
BEGIN
    UPDATE student_mp_stats
    SET cum_weighted_factor = sum_weighted_factors/gp_credits,
        cum_unweighted_factor = sum_unweighted_factors/gp_credits
	WHERE student_mp_stats.student_id = s_id and cast(student_mp_stats.marking_period_id as text) = mp_id;
  RETURN 1;
END;
$_$;


--modif Francois: fix calc_cum_gpa_mp()
--
-- Name: calc_cum_gpa_mp(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE OR REPLACE FUNCTION calc_cum_gpa_mp(character varying) RETURNS integer
    AS $_$DECLARE
  mp_id ALIAS for $1;
  mpinfo marking_periods%ROWTYPE;
  s student_mp_stats%ROWTYPE;
BEGIN
  FOR s in select student_id from student_mp_stats where cast(marking_period_id as text) = mp_id LOOP
   
    PERFORM calc_cum_gpa(mp_id, s.student_id);
    PERFORM calc_cum_cr_gpa(mp_id, s.student_id);
  END LOOP;
  RETURN 1;
END;

$_$
    LANGUAGE plpgsql;

--modif Francois: fix calc_gpa_mp() + credit()
--
-- Name: calc_gpa_mp(integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE OR REPLACE FUNCTION calc_gpa_mp(integer, character varying) RETURNS integer
    AS $_$
DECLARE
  s_id ALIAS for $1;
  mp_id ALIAS for $2;
  oldrec student_mp_stats%ROWTYPE;
BEGIN
  SELECT * INTO oldrec FROM student_mp_stats WHERE student_id = s_id and cast(marking_period_id as text) = mp_id;

  IF FOUND THEN
    UPDATE STUDENT_MP_STATS SET 
        sum_weighted_factors = rcg.sum_weighted_factors, 
        sum_unweighted_factors = rcg.sum_unweighted_factors, 
        cr_weighted_factors = rcg.cr_weighted,
        cr_unweighted_factors = rcg.cr_unweighted,
        gp_credits = rcg.gp_credits,
        cr_credits = rcg.cr_credits
        
      FROM (
      select 
        sum(weighted_gp*credit_attempted/gp_scale) as sum_weighted_factors, 
        sum(unweighted_gp*credit_attempted/gp_scale) as sum_unweighted_factors, 
        sum(credit_attempted) as gp_credits,
        sum( case when class_rank = 'Y' THEN weighted_gp*credit_attempted/gp_scale END ) as cr_weighted,
        sum( case when class_rank = 'Y' THEN unweighted_gp*credit_attempted/gp_scale END ) as cr_unweighted,
        sum( case when class_rank = 'Y' THEN credit_attempted END) as cr_credits

        from student_report_card_grades where student_id = s_id
        and cast(marking_period_id as text) = mp_id
         and not gp_scale = 0 group by student_id, marking_period_id
        ) as rcg
WHERE student_id = s_id and cast(marking_period_id as text) = mp_id;
    RETURN 1;
  ELSE
    INSERT INTO STUDENT_MP_STATS (student_id, marking_period_id, sum_weighted_factors, sum_unweighted_factors, grade_level_short, cr_weighted_factors, cr_unweighted_factors, gp_credits, cr_credits)

        select 
            srcg.student_id, (srcg.marking_period_id::text)::int, 
            sum(weighted_gp*credit_attempted/gp_scale) as sum_weighted_factors, 
            sum(unweighted_gp*credit_attempted/gp_scale) as sum_unweighted_factors, 
            eg.short_name,
            sum( case when class_rank = 'Y' THEN weighted_gp*credit_attempted/gp_scale END ) as cr_weighted,
	    sum( case when class_rank = 'Y' THEN unweighted_gp*credit_attempted/gp_scale END ) as cr_unweighted,
            sum(credit_attempted) as gp_credits,
            sum(case when class_rank = 'Y' THEN credit_attempted END) as cr_credits
        from student_report_card_grades srcg join marking_periods mp on (cast(mp.marking_period_id as text) = srcg.marking_period_id) left outer join enroll_grade eg on (eg.student_id = srcg.student_id and eg.syear = mp.syear and eg.school_id = mp.school_id)
        where srcg.student_id = s_id and cast(srcg.marking_period_id as text) = mp_id and not srcg.gp_scale = 0 
		group by srcg.student_id, srcg.marking_period_id, eg.short_name;
  END IF;
  RETURN 0;
END
$_$
    LANGUAGE plpgsql;


--
-- Name: credit(integer, character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE OR REPLACE FUNCTION credit(integer, character varying) RETURNS numeric
    AS $_$
DECLARE
	course_detail RECORD;
	mp_detail RECORD;
	values RECORD;
	
BEGIN
select * into course_detail from course_periods where course_period_id = $1;
select * into mp_detail from marking_periods where cast(marking_period_id as text) = $2;

IF course_detail.marking_period_id = mp_detail.marking_period_id THEN
	return course_detail.credits;
ELSIF course_detail.mp = 'FY' AND mp_detail.mp_type = 'semester' THEN
	select into values count(*) as mp_count from marking_periods where parent_id = course_detail.marking_period_id group by parent_id;
ELSIF course_detail.mp = 'FY' and mp_detail.mp_type = 'quarter' THEN
	select into values count(*) as mp_count from marking_periods where grandparent_id = course_detail.marking_period_id group by grandparent_id;
ELSIF course_detail.mp = 'SEM' and mp_detail.mp_type = 'quarter' THEN
	select into values count(*) as mp_count from marking_periods where parent_id = course_detail.marking_period_id group by parent_id;
ELSE
	return course_detail.credits;
END IF;

IF values.mp_count > 0 THEN
	return course_detail.credits/values.mp_count;
ELSE
	return course_detail.credits;
END IF;

END$_$
    LANGUAGE plpgsql;

--modif Francois: fix set_class_rank_mp()
--
-- Name: set_class_rank_mp(character varying); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE OR REPLACE FUNCTION set_class_rank_mp(character varying) RETURNS integer
    AS $_$
DECLARE 
	mp_id alias for $1;
BEGIN
update student_mp_stats set cum_rank = rank.rank, class_size = rank.class_size  from
(
select 
mp.syear, mp.marking_period_id, sgm.student_id, se.grade_id, sgm.cum_cr_weighted_factor
,
 (select count(*)+1 
   from student_mp_stats sgm3
   where sgm3.cum_cr_weighted_factor > sgm.cum_cr_weighted_factor
     and sgm3.marking_period_id = mp.marking_period_id 
     and sgm3.student_id in (select distinct sgm2.student_id 
                            from student_mp_stats sgm2, student_enrollment se2
                            where sgm2.student_id = se2.student_id 
                              and sgm2.marking_period_id = mp.marking_period_id 
				and se2.grade_id = se.grade_id
				and se2.syear = se.syear)
) as rank,

 (select count(*) 
   from student_mp_stats sgm4
   where
     sgm4.marking_period_id = mp.marking_period_id 
     and sgm4.student_id in (select distinct sgm5.student_id 
                            from student_mp_stats sgm5, student_enrollment se3
                            where sgm5.student_id = se3.student_id 
                              and sgm5.marking_period_id = mp.marking_period_id 
				and se3.grade_id = se.grade_id
				and se3.syear = se.syear)
) as class_size

  
from student_enrollment se, student_mp_stats sgm, marking_periods mp
 
where 
se.student_id = sgm.student_id
and sgm.marking_period_id = mp.marking_period_id
and cast(mp.marking_period_id as text) = mp_id
and se.syear = mp.syear
and not sgm.cum_cr_weighted_factor is null
order by grade_id, rank ) as rank

where student_mp_stats.marking_period_id = rank.marking_period_id
and student_mp_stats.student_id = rank.student_id;
RETURN 1;
END;
$_$
    LANGUAGE plpgsql;


--
-- Name: t_update_mp_stats(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION t_update_mp_stats() RETURNS "trigger"
    AS $$
begin

  IF tg_op = 'DELETE' THEN
	perform calc_gpa_mp(OLD.student_id::int, OLD.marking_period_id::varchar);
  ELSE
	--IF tg_op = 'INSERT' THEN
		--we need to do stuff here to gather other information since it's a new record.
	--ELSE
		--if report_card_grade_id changes, then we need to reset gp values
	--	IF NOT NEW.report_card_grade_id = OLD.report_card_grade_id THEN
			--
	perform calc_gpa_mp(NEW.student_id::int, NEW.marking_period_id::varchar);
  END IF;
  return NULL;
end
$$
    LANGUAGE plpgsql;


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: address; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE address (
    address_id numeric(10,0) NOT NULL,
    house_no numeric(5,0),
    fraction character varying(3),
    letter character varying(2),
    direction character varying(2),
    street character varying(30),
    apt character varying(5),
    zipcode character varying(10),
    plus4 character varying(4),
    city character varying(60),
    state character varying(10),
    mail_street character varying(30),
    mail_city character varying(60),
    mail_state character varying(10),
    mail_zipcode character varying(10),
    address character varying(255),
    mail_address character varying(255),
    phone character varying(30)
);




--
-- Name: address_field_categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE address_field_categories (
    id numeric NOT NULL,
    title character varying(1000) NOT NULL,
    sort_order numeric,
    residence character(1),
    mailing character(1),
    bus character(1)
);




--
-- Name: address_field_categories_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE address_field_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: address_field_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('address_field_categories_seq', 1, false);


--
-- Name: address_fields; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE address_fields (
    id numeric NOT NULL,
    type character varying(10) NOT NULL,
    search character varying(1),
    title character varying(1000) NOT NULL,
    sort_order numeric,
    select_options character varying(10000),
    category_id numeric,
    system_field character(1),
    required character varying(1),
    default_selection character varying(255)
);




--
-- Name: address_fields_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE address_fields_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: address_fields_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('address_fields_seq', 1, true);


--
-- Name: address_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE address_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: address_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('address_seq', 1, true);


--
-- Name: attendance_calendar; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE attendance_calendar (
    syear numeric(4,0) NOT NULL,
    school_id numeric NOT NULL,
    school_date date NOT NULL,
    minutes numeric,
    block character varying(10),
    calendar_id numeric NOT NULL
);




--
-- Name: attendance_calendars; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE attendance_calendars (
    school_id numeric,
    title character varying(100),
    syear numeric(4,0),
    calendar_id numeric NOT NULL,
    default_calendar character varying(1),
    rollover_id numeric
);




--
-- Name: attendance_code_categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE attendance_code_categories (
    id numeric,
    syear numeric(4,0),
    school_id numeric,
    title character varying(255),
    sort_order numeric,
    rollover_id numeric
);




--
-- Name: attendance_code_categories_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE attendance_code_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: attendance_code_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('attendance_code_categories_seq', 1, false);


--
-- Name: attendance_codes; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE attendance_codes (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    title character varying(100),
    short_name character varying(10),
    type character varying(10),
    state_code character varying(1),
    default_code character varying(1),
    table_name numeric,
    sort_order numeric
);




--
-- Name: attendance_codes_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE attendance_codes_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: attendance_codes_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('attendance_codes_seq', 4, true);


--
-- Name: attendance_completed; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE attendance_completed (
    staff_id numeric NOT NULL,
    school_date date NOT NULL,
    period_id numeric NOT NULL,
    table_name numeric NOT NULL
);




--
-- Name: attendance_day; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE attendance_day (
    student_id numeric NOT NULL,
    school_date date NOT NULL,
    minutes_present numeric,
    state_value numeric(2,1),
    syear numeric(4,0),
    marking_period_id numeric,
    comment character varying(255)
);




--
-- Name: attendance_period; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE attendance_period (
    student_id numeric NOT NULL,
    school_date date NOT NULL,
    period_id numeric NOT NULL,
    attendance_code numeric,
    attendance_teacher_code numeric,
    attendance_reason character varying(100),
    admin character varying(1),
    course_period_id numeric,
    marking_period_id numeric,
    comment character varying(100)
);




--
-- Name: billing_fees; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE billing_fees (
    student_id numeric NOT NULL,
    assigned_date date,
    due_date date,
    comments character varying(255),
    id numeric,
    title character varying(255),
    amount numeric,
    school_id numeric,
    syear numeric,
    waived_fee_id numeric,
    old_id numeric
);




--
-- Name: billing_fees_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE billing_fees_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: billing_fees_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('billing_fees_seq', 1, false);


--
-- Name: billing_payments; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE billing_payments (
    id numeric NOT NULL,
    syear numeric NOT NULL,
    school_id numeric NOT NULL,
    student_id numeric NOT NULL,
    amount numeric NOT NULL,
    payment_date date,
    comments character varying(255),
    refunded_payment_id numeric,
    lunch_payment character varying(1)
);




--
-- Name: billing_payments_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE billing_payments_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: billing_payments_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('billing_payments_seq', 1, false);


--
-- Name: calendar_events; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE calendar_events (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    school_date date,
    title character varying(50),
    description character varying(500)
);




--
-- Name: calendar_events_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE calendar_events_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: calendar_events_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('calendar_events_seq', 1, true);


--
-- Name: calendars_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE calendars_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: calendars_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('calendars_seq', 1, true);


--
-- Name: config; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE config (
	school_id numeric NOT NULL,
    title character varying(100),
    config_value character varying(255)
);




--
-- Name: course_periods; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE course_periods (
    syear numeric(4,0) NOT NULL,
    school_id numeric NOT NULL,
    course_period_id numeric NOT NULL,
    course_id numeric NOT NULL,
    title character varying(255),
    short_name character varying(25) NOT NULL,
    mp character varying(3),
    marking_period_id numeric,
    teacher_id numeric NOT NULL,
    room character varying(10),
    total_seats numeric,
    filled_seats numeric,
    does_attendance character varying(255),
    does_honor_roll character varying(1),
    does_class_rank character varying(1),
    gender_restriction character varying(1),
    house_restriction character varying(1),
    availability numeric,
    parent_id numeric,
    calendar_id numeric,
    half_day character varying(1),
    does_breakoff character varying(1),
    rollover_id numeric,
    grade_scale_id numeric,
    credits numeric
);




--
-- Name: courses; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE courses (
    syear numeric(4,0) NOT NULL,
    course_id numeric NOT NULL,
    subject_id numeric NOT NULL,
    school_id numeric NOT NULL,
    grade_level numeric,
    title character varying(100) NOT NULL,
    short_name character varying(25),
    rollover_id numeric,
    credit_hours numeric(6,2)
);




--
-- Name: course_details; Type: VIEW; Schema: public; Owner: rosariosis
--

CREATE VIEW course_details AS
    SELECT cp.school_id, cp.syear, cp.marking_period_id, c.subject_id, cp.course_id, cp.course_period_id, cp.teacher_id, c.title AS course_title, cp.title AS cp_title, cp.grade_scale_id, cp.mp, cp.credits FROM course_periods cp, courses c WHERE (cp.course_id = c.course_id);




--
-- Name: course_period_school_periods; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE course_period_school_periods (
    course_period_school_periods_id numeric NOT NULL,
    course_period_id numeric NOT NULL,
    period_id numeric NOT NULL,
    days character varying(7)
);




--
-- Name: course_period_school_periods_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE course_period_school_periods_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: course_period_school_periods_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('course_period_school_periods_seq', 1, true);


--
-- Name: course_periods_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE course_periods_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: course_periods_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('course_periods_seq', 1, true);


--
-- Name: course_subjects; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE course_subjects (
    syear numeric(4,0),
    school_id numeric,
    subject_id numeric NOT NULL,
    title character varying(100) NOT NULL,
    short_name character varying(25),
    sort_order numeric,
    rollover_id numeric
);




--
-- Name: course_subjects_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE course_subjects_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: course_subjects_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('course_subjects_seq', 1, true);


--
-- Name: courses_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE courses_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: courses_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('courses_seq', 1, true);


--
-- Name: custom; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE custom (
    student_id numeric NOT NULL
);




--
-- Name: custom_fields; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE custom_fields (
    id numeric NOT NULL,
    type character varying(10) NOT NULL,
    search character varying(1),
    title character varying(1000) NOT NULL,
    sort_order numeric,
    select_options character varying(10000),
    category_id numeric,
    system_field character(1),
    required character varying(1),
    default_selection character varying(255)
);




--
-- Name: custom_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE custom_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: custom_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('custom_seq', 1, true);


--
-- Name: discipline_categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE discipline_categories (
    id numeric,
    syear numeric(4,0),
    school_id numeric,
    title character varying(255),
    sort_order numeric,
    type character varying(30),
    options character varying(10000)
);




--
-- Name: discipline_categories_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE discipline_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: discipline_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('discipline_categories_seq', 1, false);


--
-- Name: discipline_field_usage; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE discipline_field_usage (
    id numeric NOT NULL,
    discipline_field_id numeric NOT NULL,
    syear numeric NOT NULL,
    school_id numeric NOT NULL,
    title character varying(255),
    select_options character varying(10000),
    sort_order numeric
);




--
-- Name: discipline_field_usage_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE discipline_field_usage_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: discipline_field_usage_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('discipline_field_usage_seq', 6, true);


--
-- Name: discipline_fields; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE discipline_fields (
    id numeric NOT NULL,
    title character varying(255) NOT NULL,
    short_name character varying(20),
    data_type character varying(30) NOT NULL,
    column_name character varying(255) NOT NULL
);




--
-- Name: discipline_fields_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE discipline_fields_seq
    START WITH 6
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: discipline_fields_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('discipline_fields_seq', 6, true);


--
-- Name: discipline_referrals; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE discipline_referrals (
    id numeric NOT NULL,
    syear numeric NOT NULL,
    student_id numeric NOT NULL,
    school_id numeric NOT NULL,
    staff_id numeric,
    entry_date date,
    referral_date date,
    category_1 character varying(1000),
    category_2 character varying(1000),
    category_3 character varying(1),
    category_4 character varying(1000),
    category_5 character varying(1000),
    category_6 character varying(5000)
);




--
-- Name: discipline_referrals_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE discipline_referrals_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: discipline_referrals_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('discipline_referrals_seq', 1, false);


--
-- Name: eligibility; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE eligibility (
    student_id numeric,
    syear numeric(4,0),
    school_date date,
    period_id numeric,
    eligibility_code character varying(20),
    course_period_id numeric
);




--
-- Name: eligibility_activities; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE eligibility_activities (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    title character varying(100),
    start_date date,
    end_date date
);




--
-- Name: eligibility_activities_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE eligibility_activities_seq
    START WITH 3
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: eligibility_activities_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('eligibility_activities_seq', 3, true);


--
-- Name: eligibility_completed; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE eligibility_completed (
    staff_id numeric NOT NULL,
    school_date date NOT NULL,
    period_id numeric NOT NULL
);




--
-- Name: school_gradelevels; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE school_gradelevels (
    id numeric NOT NULL,
    school_id numeric NOT NULL,
    short_name character varying(2),
    title character varying(50),
    next_grade_id numeric,
    sort_order numeric
);




--
-- Name: student_enrollment; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE student_enrollment (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    student_id numeric,
    grade_id numeric,
    start_date date,
    end_date date,
    enrollment_code numeric,
    drop_code numeric,
    next_school numeric,
    calendar_id numeric,
    last_school numeric
);




--
-- Name: enroll_grade; Type: VIEW; Schema: public; Owner: rosariosis
--

CREATE VIEW enroll_grade AS
    SELECT e.id, e.syear, e.school_id, e.student_id, e.start_date, e.end_date, sg.short_name, sg.title FROM student_enrollment e, school_gradelevels sg WHERE (e.grade_id = sg.id);




--
-- Name: VIEW enroll_grade; Type: COMMENT; Schema: public; Owner: rosariosis
--

COMMENT ON VIEW enroll_grade IS 'Provides enrollment dates and grade levels';


--
-- Name: food_service_accounts; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE food_service_accounts (
    account_id numeric NOT NULL,
    balance numeric(9,2) NOT NULL,
    transaction_id numeric
);




--
-- Name: food_service_categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE food_service_categories (
    category_id numeric NOT NULL,
    school_id numeric NOT NULL,
    menu_id numeric NOT NULL,
    title character varying(25),
    sort_order numeric
);




--
-- Name: food_service_categories_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE food_service_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: food_service_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('food_service_categories_seq', 1, true);


--
-- Name: food_service_items; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE food_service_items (
    item_id numeric NOT NULL,
    school_id numeric NOT NULL,
    short_name character varying(25),
    sort_order numeric,
    description character varying(25),
    icon character varying(50),
    price numeric(9,2) NOT NULL,
    price_reduced numeric(9,2),
    price_free numeric(9,2),
    price_staff numeric(9,2) NOT NULL
);




--
-- Name: food_service_items_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE food_service_items_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: food_service_items_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('food_service_items_seq', 4, true);


--
-- Name: food_service_menu_items; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE food_service_menu_items (
    menu_item_id numeric NOT NULL,
    school_id numeric NOT NULL,
    menu_id numeric NOT NULL,
    item_id numeric NOT NULL,
    category_id numeric,
    sort_order numeric,
    does_count character varying(1)
);




--
-- Name: food_service_menu_items_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE food_service_menu_items_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: food_service_menu_items_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('food_service_menu_items_seq', 4, true);


--
-- Name: food_service_menus; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE food_service_menus (
    menu_id numeric NOT NULL,
    school_id numeric NOT NULL,
    title character varying(25) NOT NULL,
    sort_order numeric
);




--
-- Name: food_service_menus_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE food_service_menus_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: food_service_menus_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('food_service_menus_seq', 1, true);


--
-- Name: food_service_staff_accounts; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE food_service_staff_accounts (
    staff_id numeric NOT NULL,
    status character varying(25),
    barcode character varying(50),
    balance numeric(9,2) NOT NULL,
    transaction_id numeric
);




--
-- Name: food_service_staff_transaction_items; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE food_service_staff_transaction_items (
    item_id numeric NOT NULL,
    transaction_id numeric NOT NULL,
    amount numeric(9,2),
    short_name character varying(25),
    description character varying(50)
);




--
-- Name: food_service_staff_transactions; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE food_service_staff_transactions (
    transaction_id numeric NOT NULL,
    staff_id numeric NOT NULL,
    school_id numeric,
    syear numeric(4,0),
    balance numeric(9,2),
    "timestamp" timestamp(0) without time zone,
    short_name character varying(25),
    description character varying(50),
    seller_id numeric
);




--
-- Name: food_service_staff_transactions_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE food_service_staff_transactions_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: food_service_staff_transactions_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('food_service_staff_transactions_seq', 1, true);


--
-- Name: food_service_student_accounts; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE food_service_student_accounts (
    student_id numeric NOT NULL,
    account_id numeric NOT NULL,
    discount character varying(25),
    status character varying(25),
    barcode character varying(50)
);




--
-- Name: food_service_transaction_items; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE food_service_transaction_items (
    item_id numeric NOT NULL,
    transaction_id numeric NOT NULL,
    amount numeric(9,2),
    discount character varying(25),
    short_name character varying(25),
    description character varying(50)
);




--
-- Name: food_service_transactions; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE food_service_transactions (
    transaction_id numeric NOT NULL,
    account_id numeric NOT NULL,
    student_id numeric,
    school_id numeric,
    syear numeric(4,0),
    discount character varying(25),
    balance numeric(9,2),
    "timestamp" timestamp(0) without time zone,
    short_name character varying(25),
    description character varying(50),
    seller_id numeric
);




--
-- Name: food_service_transactions_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE food_service_transactions_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: food_service_transactions_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('food_service_transactions_seq', 1, true);


--
-- Name: gradebook_assignment_types; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE gradebook_assignment_types (
    assignment_type_id numeric NOT NULL,
    staff_id numeric,
    course_id numeric,
    title character varying(100) NOT NULL,
    final_grade_percent numeric(6,5),
    sort_order numeric,
    color character varying(30)
);




--
-- Name: gradebook_assignment_types_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE gradebook_assignment_types_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: gradebook_assignment_types_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('gradebook_assignment_types_seq', 1, false);


--
-- Name: gradebook_assignments; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE gradebook_assignments (
    assignment_id numeric NOT NULL,
    staff_id numeric,
    marking_period_id numeric,
    course_period_id numeric,
    course_id numeric,
    assignment_type_id numeric NOT NULL,
    title character varying(100) NOT NULL,
    assigned_date date,
    due_date date,
    points numeric NOT NULL,
    description character varying(1000)
);




--
-- Name: gradebook_assignments_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE gradebook_assignments_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: gradebook_assignments_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('gradebook_assignments_seq', 1, true);


--
-- Name: gradebook_grades; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE gradebook_grades (
    student_id numeric NOT NULL,
    period_id numeric,
    course_period_id numeric NOT NULL,
    assignment_id numeric NOT NULL,
    points numeric(6,2),
    comment character varying(100)
);




--
-- Name: grades_completed; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE grades_completed (
    staff_id numeric NOT NULL,
    marking_period_id character varying(10) NOT NULL,
    course_period_id numeric NOT NULL
);




--
-- Name: history_marking_periods; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE history_marking_periods (
    parent_id integer,
    mp_type character(20),
    name character(30),
    short_name character varying(10),
    post_end_date date,
    school_id integer,
    syear integer,
    marking_period_id integer NOT NULL
);




--
-- Name: lunch_period; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE lunch_period (
    student_id numeric NOT NULL,
    school_date date NOT NULL,
    period_id numeric NOT NULL,
    attendance_code numeric,
    attendance_teacher_code numeric,
    attendance_reason character varying(100),
    admin character varying(1),
    course_period_id numeric,
    marking_period_id numeric,
    comment character varying(100),
    table_name numeric
);




--
-- Name: marking_period_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE marking_period_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: marking_period_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('marking_period_seq', 11, true);


--
-- Name: school_marking_periods; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE school_marking_periods (
    marking_period_id numeric NOT NULL,
    syear numeric(4,0),
    mp character varying(3) NOT NULL,
    school_id numeric,
    parent_id numeric,
    title character varying(50),
    short_name character varying(10),
    sort_order numeric,
    start_date date NOT NULL,
    end_date date NOT NULL,
    post_start_date date,
    post_end_date date,
    does_grades character varying(1),
    does_comments character varying(1),
    rollover_id numeric
);




--
-- Name: marking_periods; Type: VIEW; Schema: public; Owner: rosariosis
--

CREATE VIEW marking_periods AS
    SELECT school_marking_periods.marking_period_id, 'Rosario'::text AS mp_source, school_marking_periods.syear, school_marking_periods.school_id, CASE WHEN ((school_marking_periods.mp)::text = 'FY'::text) THEN 'year'::text WHEN ((school_marking_periods.mp)::text = 'SEM'::text) THEN 'semester'::text WHEN ((school_marking_periods.mp)::text = 'QTR'::text) THEN 'quarter'::text ELSE NULL::text END AS mp_type, school_marking_periods.title, school_marking_periods.short_name, school_marking_periods.sort_order, CASE WHEN (school_marking_periods.parent_id > (0)::numeric) THEN school_marking_periods.parent_id ELSE ((-1))::numeric END AS parent_id, CASE WHEN ((SELECT smp.parent_id FROM school_marking_periods smp WHERE (smp.marking_period_id = school_marking_periods.parent_id)) > (0)::numeric) THEN (SELECT smp.parent_id FROM school_marking_periods smp WHERE (smp.marking_period_id = school_marking_periods.parent_id)) ELSE ((-1))::numeric END AS grandparent_id, school_marking_periods.start_date, school_marking_periods.end_date, school_marking_periods.post_start_date, school_marking_periods.post_end_date, school_marking_periods.does_grades, school_marking_periods.does_comments FROM school_marking_periods UNION SELECT history_marking_periods.marking_period_id, 'History'::text AS mp_source, history_marking_periods.syear, history_marking_periods.school_id, history_marking_periods.mp_type, history_marking_periods.name AS title, history_marking_periods.short_name, NULL::numeric AS sort_order, history_marking_periods.parent_id, (-1) AS grandparent_id, NULL::date AS start_date, history_marking_periods.post_end_date AS end_date, NULL::date AS post_start_date, history_marking_periods.post_end_date, 'Y'::character varying AS does_grades, NULL::character varying AS does_comments FROM history_marking_periods;




--
-- Name: moodlexrosario; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE moodlexrosario (
    "column" character varying(100) NOT NULL,
    rosario_id numeric NOT NULL,
    moodle_id numeric NOT NULL
);




--
-- Name: people; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE people (
    person_id numeric(10,0) NOT NULL,
    last_name character varying(50) NOT NULL,
    first_name character varying(50) NOT NULL,
    middle_name character varying(50)
);




--
-- Name: people_field_categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE people_field_categories (
    id numeric NOT NULL,
    title character varying(1000),
    sort_order numeric,
    custody character(1),
    emergency character(1)
);




--
-- Name: people_field_categories_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE people_field_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: people_field_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('people_field_categories_seq', 1, false);


--
-- Name: people_fields; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE people_fields (
    id numeric NOT NULL,
    type character varying(10),
    search character varying(1),
    title character varying(1000),
    sort_order numeric,
    select_options character varying(10000),
    category_id numeric,
    system_field character(1),
    required character varying(1),
    default_selection character varying(255)
);




--
-- Name: people_fields_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE people_fields_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: people_fields_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('people_fields_seq', 1, true);


--
-- Name: people_join_contacts; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE people_join_contacts (
    id numeric NOT NULL,
    person_id numeric,
    title character varying(100),
    value character varying(100)
);




--
-- Name: people_join_contacts_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE people_join_contacts_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: people_join_contacts_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('people_join_contacts_seq', 1, true);


--
-- Name: people_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE people_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: people_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('people_seq', 1, true);


--
-- Name: portal_notes; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE portal_notes (
    id numeric NOT NULL,
    school_id numeric,
    syear numeric(4,0),
    title character varying(255),
    content character varying(5000),
    sort_order numeric,
    published_user numeric,
    published_date timestamp(0) without time zone,
    start_date date,
    end_date date,
    published_profiles character varying(255),
    file_attached character varying(270)
);




--
-- Name: portal_notes_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE portal_notes_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: portal_notes_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('portal_notes_seq', 1, false);


--
-- Name: portal_poll_questions; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE portal_poll_questions (
    id numeric NOT NULL,
    portal_poll_id numeric NOT NULL,
    question character varying(255),
    type character varying(20),
    options character varying(5000),
    votes character varying(255)
);




--
-- Name: portal_poll_questions_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE portal_poll_questions_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: portal_poll_questions_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('portal_poll_questions_seq', 1, false);


--
-- Name: portal_polls; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE portal_polls (
    id numeric NOT NULL,
    school_id numeric,
    syear numeric(4,0),
    title character varying(255),
    votes_number numeric,
    display_votes character varying(1),
    sort_order numeric,
    published_user numeric,
    published_date timestamp(0) without time zone,
    start_date date,
    end_date date,
    published_profiles character varying(255),
	students_teacher_id numeric,
    excluded_users text
);




--
-- Name: portal_polls_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE portal_polls_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: portal_polls_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('portal_polls_seq', 1, false);


--
-- Name: profile_exceptions; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE profile_exceptions (
    profile_id numeric,
    modname character varying(255),
    can_use character varying(1),
    can_edit character varying(1)
);




--
-- Name: program_config; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE program_config (
    syear numeric(4,0),
    school_id numeric,
    program character varying(255),
    title character varying(100),
    value character varying(100)
);




--
-- Name: program_user_config; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE program_user_config (
    user_id numeric NOT NULL,
    program character varying(255),
    title character varying(100),
    value character varying(100),
    school_id numeric
);




--
-- Name: report_card_comment_categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE report_card_comment_categories (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    course_id numeric,
    sort_order numeric,
    title character varying(1000),
    rollover_id numeric,
    color character varying(30)
);




--
-- Name: report_card_comment_categories_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE report_card_comment_categories_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: report_card_comment_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('report_card_comment_categories_seq', 1, true);


--
-- Name: report_card_comment_code_scales; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE report_card_comment_code_scales (
    id numeric NOT NULL,
    school_id numeric NOT NULL,
    title character varying(25),
    comment character varying(100),
    sort_order numeric,
    rollover_id numeric
);




--
-- Name: report_card_comment_code_scales_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE report_card_comment_code_scales_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: report_card_comment_code_scales_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('report_card_comment_code_scales_seq', 1, false);


--
-- Name: report_card_comment_codes; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE report_card_comment_codes (
    id numeric NOT NULL,
    school_id numeric NOT NULL,
    scale_id numeric NOT NULL,
    title character varying(5) NOT NULL,
    short_name character varying(100),
    comment character varying(100),
    sort_order numeric
);




--
-- Name: report_card_comment_codes_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE report_card_comment_codes_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: report_card_comment_codes_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('report_card_comment_codes_seq', 1, false);


--
-- Name: report_card_comments; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE report_card_comments (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    course_id numeric,
    category_id numeric,
    scale_id numeric,
    sort_order numeric,
    title character varying(5000)
);




--
-- Name: report_card_comments_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE report_card_comments_seq
    START WITH 3
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: report_card_comments_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('report_card_comments_seq', 3, true);


--
-- Name: report_card_grade_scales; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE report_card_grade_scales (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric NOT NULL,
    title character varying(300),
    comment character varying(1000),
    hhr_gpa_value numeric(4,2),
    hr_gpa_value numeric(4,2),
    sort_order numeric,
    rollover_id numeric,
    gp_scale numeric(10,3),
    hrs_gpa_value numeric(4,2)
);




--
-- Name: report_card_grade_scales_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE report_card_grade_scales_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: report_card_grade_scales_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('report_card_grade_scales_seq', 1, true);


--
-- Name: report_card_grades; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE report_card_grades (
    id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    title character varying(100),
    sort_order numeric,
    gpa_value numeric(4,2),
    break_off numeric,
    comment character varying(1000),
    grade_scale_id numeric,
    unweighted_gp numeric(4,2)
);




--
-- Name: report_card_grades_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE report_card_grades_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: report_card_grades_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('report_card_grades_seq', 15, true);


--
-- Name: schedule; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE schedule (
    syear numeric(4,0) NOT NULL,
    school_id numeric,
    student_id numeric NOT NULL,
    start_date date NOT NULL,
    end_date date,
    modified_date date,
    modified_by character varying(255),
    course_id numeric NOT NULL,
    course_period_id numeric NOT NULL,
    mp character varying(3),
    marking_period_id numeric,
    scheduler_lock character varying(1),
    id numeric
);




--
-- Name: schedule_requests; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE schedule_requests (
    syear numeric(4,0),
    school_id numeric,
    request_id numeric NOT NULL,
    student_id numeric,
    subject_id numeric,
    course_id numeric,
    marking_period_id numeric,
    priority numeric,
    with_teacher_id numeric,
    not_teacher_id numeric,
    with_period_id numeric,
    not_period_id numeric
);




--
-- Name: schedule_requests_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE schedule_requests_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: schedule_requests_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('schedule_requests_seq', 1, true);


--
-- Name: schedule_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE schedule_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: schedule_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('schedule_seq', 1, false);


--
-- Name: school_gradelevels_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE school_gradelevels_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: school_gradelevels_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('school_gradelevels_seq', 9, true);


--
-- Name: school_periods; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE school_periods (
    period_id numeric NOT NULL,
    syear numeric(4,0),
    school_id numeric,
    sort_order numeric,
    title character varying(100),
    short_name character varying(10),
    length numeric,
    start_time character varying(10),
    end_time character varying(10),
    block character varying(10),
    attendance character varying(1),
    rollover_id numeric
);




--
-- Name: school_periods_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE school_periods_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: school_periods_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('school_periods_seq', 11, true);


--
-- Name: schools; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE schools (
    syear numeric(4,0) NOT NULL,
    id numeric NOT NULL,
    title character varying(100),
    address character varying(100),
    city character varying(100),
    state character varying(10),
    zipcode character varying(10),
    phone character varying(30),
    principal character varying(100),
    www_address character varying(100),
    school_number character varying(50),
    short_name character varying(25),
    reporting_gp_scale numeric(10,3),
	number_days_rotation numeric(1,0)
);




--
-- Name: schools_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE schools_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: schools_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('schools_seq', 1, true);


--
-- Name: staff; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE staff (
    syear numeric(4,0),
    staff_id numeric NOT NULL,
    current_school_id numeric,
    title character varying(5),
    first_name character varying(100) NOT NULL,
    last_name character varying(100) NOT NULL,
    middle_name character varying(100),
    name_suffix character varying(3),
    username character varying(100),
    password character varying(106),
    phone character varying(100),
    email character varying(100),
    profile character varying(30),
    homeroom character varying(5),
    schools character varying(255),
    last_login timestamp(0) without time zone,
    failed_login numeric,
    profile_id numeric,
    rollover_id numeric
);




--
-- Name: staff_exceptions; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE staff_exceptions (
    user_id numeric NOT NULL,
    modname character varying(255),
    can_use character varying(1),
    can_edit character varying(1)
);




--
-- Name: staff_field_categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE staff_field_categories (
    id numeric NOT NULL,
    title character varying(1000) NOT NULL,
    sort_order numeric,
    columns numeric(4,0),
    include character varying(100),
    admin character(1),
    teacher character(1),
    parent character(1),
    "none" character(1)
);




--
-- Name: staff_field_categories_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE staff_field_categories_seq
    START WITH 3
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: staff_field_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('staff_field_categories_seq', 3, true);


--
-- Name: staff_fields; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE staff_fields (
    id numeric NOT NULL,
    type character varying(10) NOT NULL,
    search character varying(1),
    title character varying(1000) NOT NULL,
    sort_order numeric,
    select_options character varying(10000),
    category_id numeric,
    system_field character(1),
    required character varying(1),
    default_selection character varying(255)
);




--
-- Name: staff_fields_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE staff_fields_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: staff_fields_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('staff_fields_seq', 1, true);


--
-- Name: staff_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE staff_seq
    START WITH 3
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: staff_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('staff_seq', 3, true);


--
-- Name: student_eligibility_activities; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE student_eligibility_activities (
    syear numeric(4,0),
    student_id numeric,
    activity_id numeric
);




--
-- Name: student_enrollment_codes; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE student_enrollment_codes (
    id numeric,
    syear numeric(4,0),
    title character varying(100),
    short_name character varying(10),
    type character varying(4),
    default_code character varying(1),
    sort_order numeric
);




--
-- Name: student_enrollment_codes_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE student_enrollment_codes_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: student_enrollment_codes_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('student_enrollment_codes_seq', 6, true);


--
-- Name: student_enrollment_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE student_enrollment_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: student_enrollment_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('student_enrollment_seq', 1, true);


--
-- Name: student_field_categories; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE student_field_categories (
    id numeric NOT NULL,
    title character varying(1000) NOT NULL,
    sort_order numeric,
    columns numeric(4,0),
    include character varying(100)
);




--
-- Name: student_field_categories_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE student_field_categories_seq
    START WITH 5
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: student_field_categories_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('student_field_categories_seq', 5, true);


--
-- Name: student_medical; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE student_medical (
    id numeric NOT NULL,
    student_id numeric,
    type character varying(25),
    medical_date date,
    comments character varying(100)
);




--
-- Name: student_medical_alerts; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE student_medical_alerts (
    id numeric NOT NULL,
    student_id numeric,
    title character varying(100)
);




--
-- Name: student_medical_alerts_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE student_medical_alerts_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: student_medical_alerts_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('student_medical_alerts_seq', 1, false);


--
-- Name: student_medical_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE student_medical_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: student_medical_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('student_medical_seq', 1, false);


--
-- Name: student_medical_visits; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE student_medical_visits (
    id numeric NOT NULL,
    student_id numeric,
    school_date date,
    time_in character varying(20),
    time_out character varying(20),
    reason character varying(100),
    result character varying(100),
    comments character varying(255)
);




--
-- Name: student_medical_visits_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE student_medical_visits_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: student_medical_visits_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('student_medical_visits_seq', 1, false);


--
-- Name: student_mp_comments; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE student_mp_comments (
    student_id numeric NOT NULL,
    syear numeric(4,0) NOT NULL,
    marking_period_id numeric NOT NULL,
    comment text
);




--
-- Name: student_mp_stats; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE student_mp_stats (
    student_id integer NOT NULL,
    marking_period_id integer NOT NULL,
    cum_weighted_factor numeric,
    cum_unweighted_factor numeric,
    cum_rank integer,
    mp_rank integer,
    class_size integer,
    sum_weighted_factors numeric,
    sum_unweighted_factors numeric,
    count_weighted_factors numeric,
    count_unweighted_factors numeric,
    grade_level_short character varying(3),
    cr_weighted_factors numeric,
    cr_unweighted_factors numeric,
    count_cr_factors integer,
    cum_cr_weighted_factor numeric,
    cum_cr_unweighted_factor numeric,
    credit_attempted numeric,
    credit_earned numeric,
    gp_credits numeric,
    cr_credits numeric,
    comments character varying(75)
);




--
-- Name: student_report_card_comments; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE student_report_card_comments (
    syear numeric(4,0) NOT NULL,
    school_id numeric,
    student_id numeric NOT NULL,
    course_period_id numeric NOT NULL,
    report_card_comment_id numeric NOT NULL,
    comment character varying(5),
    marking_period_id character varying(10) NOT NULL
);




--
-- Name: student_report_card_grades; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE student_report_card_grades (
    syear numeric(4,0),
    school_id numeric,
    student_id numeric NOT NULL,
    course_period_id numeric,
    report_card_grade_id numeric,
    report_card_comment_id numeric,
    comment character varying(255),
    grade_percent numeric(4,1),
    marking_period_id character varying(10) NOT NULL,
    grade_letter character varying(5),
    weighted_gp numeric,
    unweighted_gp numeric,
    gp_scale numeric,
    credit_attempted numeric,
    credit_earned numeric,
    credit_category character varying(10),
    course_title character varying(100),
    id integer NOT NULL,
    school character varying(255),
    class_rank character varying(1),
    credit_hours numeric(6,2)
);




--
-- Name: student_report_card_grades_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE student_report_card_grades_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: student_report_card_grades_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('student_report_card_grades_seq', 1, false);


--
-- Name: students; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE students (
    student_id numeric NOT NULL,
    last_name character varying(50) NOT NULL,
    first_name character varying(50) NOT NULL,
    middle_name character varying(50),
    name_suffix character varying(3),
    username character varying(100),
    password character varying(106),
    last_login timestamp(0) without time zone,
    failed_login numeric,
    custom_200000000 character varying(255),
    custom_200000001 character varying(255),
    custom_200000002 character varying(255),
    custom_200000003 character varying(255),
    custom_200000004 date,
    custom_200000005 character varying(255),
    custom_200000006 character varying(255),
    custom_200000007 character varying(255),
    custom_200000008 character varying(255),
    custom_200000009 character varying(2052),
    custom_200000010 character(1),
    custom_200000011 character varying(2052)
);




--
-- Name: students_join_address; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE students_join_address (
    id numeric(10,0) NOT NULL,
    student_id numeric NOT NULL,
    address_id numeric(10,0) NOT NULL,
    contact_seq numeric(10,0),
    gets_mail character varying(1),
    primary_residence character varying(1),
    legal_residence character varying(1),
    am_bus character varying(1),
    pm_bus character varying(1),
    mailing character varying(1),
    residence character varying(1),
    bus character varying(1),
    bus_pickup character varying(1),
    bus_dropoff character varying(1)
);




--
-- Name: students_join_address_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE students_join_address_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: students_join_address_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('students_join_address_seq', 1, true);


--
-- Name: students_join_people; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE students_join_people (
    id numeric(10,0) NOT NULL,
    student_id numeric NOT NULL,
    person_id numeric(10,0) NOT NULL,
    address_id numeric,
    custody character varying(1),
    emergency character varying(1),
    student_relation character varying(100)
);




--
-- Name: students_join_people_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE students_join_people_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: students_join_people_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('students_join_people_seq', 1, true);


--
-- Name: students_join_users; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE students_join_users (
    student_id numeric NOT NULL,
    staff_id numeric NOT NULL
);




--
-- Name: students_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE students_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: students_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('students_seq', 1, true);


--
-- Name: templates; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE templates (
    modname character varying(255) NOT NULL,
    staff_id numeric NOT NULL,
    template text
);




--
-- Name: transcript_grades; Type: VIEW; Schema: public; Owner: rosariosis
--

CREATE VIEW transcript_grades AS
    SELECT mp.syear, mp.school_id, mp.marking_period_id, mp.mp_type, mp.short_name, mp.parent_id, mp.grandparent_id, (SELECT mp2.end_date FROM (student_report_card_grades JOIN marking_periods mp2 ON (((mp2.marking_period_id)::text = (student_report_card_grades.marking_period_id)::text))) WHERE (((student_report_card_grades.student_id = (sms.student_id)::numeric) AND (((student_report_card_grades.marking_period_id)::text = (mp.parent_id)::text) OR ((student_report_card_grades.marking_period_id)::text = (mp.grandparent_id)::text))) AND ((student_report_card_grades.course_title)::text = (srcg.course_title)::text)) ORDER BY mp2.end_date LIMIT 1) AS parent_end_date, mp.end_date, sms.student_id, (sms.cum_weighted_factor * schools.reporting_gp_scale) AS cum_weighted_gpa, (sms.cum_unweighted_factor * schools.reporting_gp_scale) AS cum_unweighted_gpa, sms.cum_rank, sms.mp_rank, sms.class_size, ((sms.sum_weighted_factors / sms.count_weighted_factors) * schools.reporting_gp_scale) AS weighted_gpa, ((sms.sum_unweighted_factors / sms.count_unweighted_factors) * schools.reporting_gp_scale) AS unweighted_gpa, sms.grade_level_short, srcg.comment, srcg.grade_percent, srcg.grade_letter, srcg.weighted_gp, srcg.unweighted_gp, srcg.gp_scale, srcg.credit_attempted, srcg.credit_earned, srcg.course_title, srcg.school AS school_name, schools.reporting_gp_scale AS school_scale, ((sms.cr_weighted_factors / (sms.count_cr_factors)::numeric) * schools.reporting_gp_scale) AS cr_weighted_gpa, ((sms.cr_unweighted_factors / (sms.count_cr_factors)::numeric) * schools.reporting_gp_scale) AS cr_unweighted_gpa, (sms.cum_cr_weighted_factor * schools.reporting_gp_scale) AS cum_cr_weighted_gpa, (sms.cum_cr_unweighted_factor * schools.reporting_gp_scale) AS cum_cr_unweighted_gpa, srcg.class_rank, sms.comments, srcg.credit_hours FROM (((marking_periods mp JOIN student_report_card_grades srcg ON (((mp.marking_period_id)::text = (srcg.marking_period_id)::text))) JOIN student_mp_stats sms ON ((((sms.marking_period_id)::numeric = mp.marking_period_id) AND ((sms.student_id)::numeric = srcg.student_id)))) JOIN schools ON (((mp.school_id = schools.id) AND (mp.syear = schools.syear)))) ORDER BY srcg.course_period_id;




--
-- Name: user_profiles; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE user_profiles (
    id numeric,
    profile character varying(30),
    title character varying(100)
);




--
-- Name: user_profiles_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE user_profiles_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: user_profiles_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('user_profiles_seq', 3, true);


--
-- Data for Name: address; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO address VALUES (0, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'No Address', NULL, NULL);


--
-- Data for Name: address_field_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: address_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: attendance_calendar; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: attendance_calendars; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO attendance_calendars VALUES (1, 'Main', 2013, 1, 'Y', NULL);


--
-- Data for Name: attendance_code_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: attendance_codes; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO attendance_codes VALUES (1, 2013, 1, 'Absent', 'A', 'teacher', 'A', NULL, 0, NULL);
INSERT INTO attendance_codes VALUES (2, 2013, 1, 'Present', 'P', 'teacher', 'P', 'Y', 0, NULL);
INSERT INTO attendance_codes VALUES (3, 2013, 1, 'Tardy', 'T', 'teacher', 'P', NULL, 0, NULL);
INSERT INTO attendance_codes VALUES (4, 2013, 1, 'Excused Absence', 'E', 'official', 'A', NULL, 0, NULL);


--
-- Data for Name: attendance_completed; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: attendance_day; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: attendance_period; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: billing_fees; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: billing_payments; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: calendar_events; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO config VALUES (0, 'LOGIN', 'No');
INSERT INTO config VALUES (0, 'TITLE', 'Rosario Student Information System');
INSERT INTO config VALUES (1, 'SCHOOL_SYEAR_OVER_2_YEARS', 'Y');
INSERT INTO config VALUES (1, 'ATTENDANCE_FULL_DAY_MINUTES', '300');
INSERT INTO config VALUES (1, 'STUDENTS_USE_MAILING', NULL);


--
-- Data for Name: course_period_school_periods; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: course_periods; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: course_subjects; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: courses; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: custom; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: custom_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO custom_fields VALUES (200000000, 'select', NULL, 'Gender', 0, 'Male
Female', 1, 'Y', 'Y', NULL);
INSERT INTO custom_fields VALUES (200000001, 'select', NULL, 'Ethnicity', 1, 'White, Non-Hispanic
Black, Non-Hispanic
Amer. Indian or Alaskan Native
Asian or Pacific Islander
Hispanic
Other', 1, 'Y', 'Y', NULL);
INSERT INTO custom_fields VALUES (200000002, 'text', NULL, 'Common Name', 2, NULL, 1, 'Y', NULL, NULL);
INSERT INTO custom_fields VALUES (200000003, 'text', NULL, 'Social Security', 3, NULL, 1, 'Y', NULL, NULL);
INSERT INTO custom_fields VALUES (200000004, 'date', NULL, 'Birthdate', 4, NULL, 1, 'Y', NULL, NULL);
INSERT INTO custom_fields VALUES (200000005, 'select', NULL, 'Language', 5, 'English
Spanish', 1, 'Y', NULL, NULL);
INSERT INTO custom_fields VALUES (200000006, 'text', NULL, 'Physician', 6, NULL, 2, 'Y', NULL, NULL);
INSERT INTO custom_fields VALUES (200000007, 'text', NULL, 'Physician Phone', 7, NULL, 2, 'Y', NULL, NULL);
INSERT INTO custom_fields VALUES (200000008, 'text', NULL, 'Preferred Hospital', 8, NULL, 2, 'Y', NULL, NULL);
INSERT INTO custom_fields VALUES (200000009, 'textarea', NULL, 'Comments', 9, NULL, 2, 'Y', NULL, NULL);
INSERT INTO custom_fields VALUES (200000010, 'radio', NULL, 'Has Doctor''s Note', 10, NULL, 2, 'Y', NULL, NULL);
INSERT INTO custom_fields VALUES (200000011, 'textarea', NULL, 'Doctor''s Note Comments', 11, NULL, 2, 'Y', NULL, NULL);


--
-- Data for Name: discipline_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: discipline_field_usage; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO discipline_field_usage VALUES (1, 3, 2013, 1, 'Parents Contacted By Teacher', '', 4);
INSERT INTO discipline_field_usage VALUES (2, 4, 2013, 1, 'Parent Contacted by Administrator', '', 5);
INSERT INTO discipline_field_usage VALUES (3, 6, 2013, 1, 'Comments', '', 6);
INSERT INTO discipline_field_usage VALUES (4, 1, 2013, 1, 'Violation', 'Skipping Class
Profanity, vulgarity, offensive language
Insubordination (Refusal to Comply, Disrespectful Behavior)
Inebriated (Alcohol or Drugs)
Talking out of Turn
Harassment
Fighting
Public Display of Affection
Other', 1);
INSERT INTO discipline_field_usage VALUES (5, 2, 2013, 1, 'Detention Assigned', '10 Minutes
20 Minutes
30 Minutes
Discuss Suspension', 2);
INSERT INTO discipline_field_usage VALUES (6, 5, 2013, 1, 'Suspensions (Office Only)', 'Half Day
In School Suspension
1 Day
2 Days
3 Days
5 Days
7 Days
Expulsion', 3);


--
-- Data for Name: discipline_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO discipline_fields VALUES (1, 'Violation', '', 'multiple_checkbox', 'CATEGORY_1');
INSERT INTO discipline_fields VALUES (2, 'Detention Assigned', '', 'multiple_radio', 'CATEGORY_2');
INSERT INTO discipline_fields VALUES (3, 'Parents Contacted By Teacher', '', 'checkbox', 'CATEGORY_3');
INSERT INTO discipline_fields VALUES (4, 'Parent Contacted by Administrator', '', 'text', 'CATEGORY_4');
INSERT INTO discipline_fields VALUES (5, 'Suspensions (Office Only)', '', 'multiple_checkbox', 'CATEGORY_5');
INSERT INTO discipline_fields VALUES (6, 'Comments', '', 'textarea', 'CATEGORY_6');


--
-- Data for Name: discipline_referrals; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: eligibility; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: eligibility_activities; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO eligibility_activities VALUES (1, 2013, 1, 'Boy''s Basketball', '2013-10-01', '2014-04-14');
INSERT INTO eligibility_activities VALUES (2, 2013, 1, 'Chess Team', '2013-09-01', '2014-06-04');
INSERT INTO eligibility_activities VALUES (3, 2013, 1, 'Girl''s Basketball', '2013-10-01', '2014-04-15');


--
-- Data for Name: eligibility_completed; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: food_service_accounts; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO food_service_accounts VALUES (1, 0.00, NULL);


--
-- Data for Name: food_service_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO food_service_categories VALUES (1, 1, 1, 'Lunch Items', 1);


--
-- Data for Name: food_service_items; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO food_service_items VALUES (1, 1, 'HOTL', 1, 'Student Lunch', 'Lunch.png', 1.65, 0.40, 0.00, 2.35);
INSERT INTO food_service_items VALUES (2, 1, 'MILK', 2, 'Milk', 'Milk.png', 0.25, NULL, NULL, 0.50);
INSERT INTO food_service_items VALUES (3, 1, 'XTRA', 3, 'Extra', 'Sandwich.png', 0.50, NULL, NULL, 1.00);
INSERT INTO food_service_items VALUES (4, 1, 'PIZZA', 4, 'Extra Pizza', 'Pizza.png', 1.00, NULL, NULL, 1.00);


--
-- Data for Name: food_service_menu_items; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO food_service_menu_items VALUES (1, 1, 1, 1, 1, NULL, NULL);
INSERT INTO food_service_menu_items VALUES (2, 1, 1, 2, 1, NULL, NULL);
INSERT INTO food_service_menu_items VALUES (3, 1, 1, 3, 1, NULL, NULL);
INSERT INTO food_service_menu_items VALUES (4, 1, 1, 4, 1, NULL, NULL);


--
-- Data for Name: food_service_menus; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO food_service_menus VALUES (1, 1, 'Lunch', 1);


--
-- Data for Name: food_service_staff_accounts; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: food_service_staff_transaction_items; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: food_service_staff_transactions; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: food_service_student_accounts; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO food_service_student_accounts VALUES (1, 1, NULL, NULL, '1000001');


--
-- Data for Name: food_service_transaction_items; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: food_service_transactions; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: gradebook_assignment_types; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: gradebook_assignments; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: gradebook_grades; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: grades_completed; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: history_marking_periods; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: lunch_period; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: moodlexrosario; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO moodlexrosario VALUES ('staff_id', 1, 2);


--
-- Data for Name: people; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: people_field_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: people_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: people_join_contacts; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: portal_notes; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: portal_poll_questions; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: portal_polls; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: profile_exceptions; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO profile_exceptions VALUES (1, 'School_Setup/PortalNotes.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/Schools.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/Schools.php&new_school=true', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/CopySchool.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/MarkingPeriods.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/Calendar.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/Periods.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/GradeLevels.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/Rollover.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/Student.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/Student.php&include=General_Info&student_id=new', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/AssignOtherInfo.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/AddUsers.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/AdvancedReport.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/AddDrop.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/Letters.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/MailingLabels.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/StudentLabels.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/PrintStudentInfo.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/StudentFields.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/AddressFields.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/PeopleFields.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/EnrollmentCodes.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/Student.php&category_id=1', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/Student.php&category_id=3', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Students/Student.php&category_id=2', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/User.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/User.php&staff_id=new', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/AddStudents.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/Preferences.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/Profiles.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/Exceptions.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/UserFields.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/TeacherPrograms.php&include=Eligibility/EnterEligibility.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/User.php&category_id=1', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/User.php&category_id=2', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/User.php&category_id=3', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/Schedule.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/Requests.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/MassSchedule.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/MassRequests.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/MassDrops.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/PrintSchedules.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/PrintClassLists.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/PrintClassPictures.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/PrintRequests.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/ScheduleReport.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/RequestsReport.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/UnfilledRequests.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/IncompleteSchedules.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/AddDrop.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/Courses.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/Scheduler.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/ReportCards.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/HonorRoll.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/CalcGPA.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/FixGPA.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/Transcripts.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/StudentGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/GradeBreakdown.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/FinalGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/GPARankList.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/GPAMPList.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/ReportCardGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/ReportCardComments.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/ReportCardCommentCodes.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/EditHistoryMarkingPeriods.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/EditReportCardGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/TeacherPrograms.php&include=Grades/InputFinalGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/TeacherPrograms.php&include=Grades/Grades.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/TeacherPrograms.php&include=Grades/AnomalousGrades.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Attendance/Administration.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Attendance/AddAbsences.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Attendance/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Attendance/Percent.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Attendance/Percent.php&list_by_day=true', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Attendance/DailySummary.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Attendance/StudentSummary.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Attendance/FixDailyAttendance.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Attendance/DuplicateAttendance.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Attendance/AttendanceCodes.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Users/TeacherPrograms.php&include=Attendance/TakeAttendance.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Grades/HonorRollSubject.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Eligibility/Student.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Eligibility/AddActivity.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Eligibility/StudentList.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Eligibility/TeacherCompletion.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Eligibility/Activities.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Eligibility/EntryTimes.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/Accounts.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/Statements.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/Transactions.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/ServeMenus.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/ActivityReport.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/TransactionsReport.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/MenuReports.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/Reminders.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/DailyMenus.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/MenuItems.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/Menus.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Food_Service/Kiosk.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Resources/Redirect.php&to=doc', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Resources/Redirect.php&to=videohelp', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Resources/Redirect.php&to=forums', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Resources/Redirect.php&to=translate', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (2, 'School_Setup/Schools.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'School_Setup/MarkingPeriods.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'School_Setup/Calendar.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/Student.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/AddUsers.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/AdvancedReport.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/StudentLabels.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/Letters.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/Student.php&category_id=1', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/Student.php&category_id=3', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Students/Student.php&category_id=4', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (2, 'Users/User.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Users/Preferences.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Users/User.php&category_id=1', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Users/User.php&category_id=2', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Users/User.php&category_id=3', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Scheduling/Schedule.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Scheduling/PrintSchedules.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Scheduling/PrintClassLists.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Scheduling/PrintClassPictures.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/InputFinalGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/ReportCards.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/Grades.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/Assignments.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/Assignments-new.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/AnomalousGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/ProgressReports.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/StudentGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/FinalGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/Configuration.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/ReportCardGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/ReportCardComments.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Grades/ReportCardCommentCodes.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Attendance/TakeAttendance.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Attendance/DailySummary.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Attendance/StudentSummary.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Eligibility/EnterEligibility.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Food_Service/Accounts.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Food_Service/Statements.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Food_Service/DailyMenus.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Food_Service/MenuItems.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Resources/Redirect.php&to=doc', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (2, 'Resources/Redirect.php&to=videohelp', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (2, 'Resources/Redirect.php&to=forums', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (2, 'Resources/Redirect.php&to=translate', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (3, 'School_Setup/Schools.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'School_Setup/Calendar.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Students/Student.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Students/Student.php&category_id=1', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Students/Student.php&category_id=3', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Users/User.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Users/Preferences.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Users/User.php&category_id=1', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Users/User.php&category_id=2', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Users/User.php&category_id=3', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Scheduling/Schedule.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Scheduling/PrintClassPictures.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Scheduling/Requests.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Grades/StudentGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Grades/FinalGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Grades/ReportCards.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Grades/Transcripts.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Grades/GPARankList.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Attendance/StudentSummary.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Attendance/DailySummary.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Eligibility/Student.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Eligibility/StudentList.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Food_Service/Accounts.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Food_Service/Statements.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Food_Service/DailyMenus.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Food_Service/MenuItems.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Resources/Redirect.php&to=doc', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (3, 'Resources/Redirect.php&to=videohelp', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (3, 'Resources/Redirect.php&to=forums', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (3, 'Resources/Redirect.php&to=translate', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (0, 'School_Setup/Schools.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'School_Setup/Calendar.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Students/Student.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Students/Student.php&category_id=1', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Students/Student.php&category_id=3', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Scheduling/Schedule.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Scheduling/PrintClassPictures.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Scheduling/Requests.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Grades/StudentGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Grades/FinalGrades.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Grades/ReportCards.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Grades/Transcripts.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Grades/GPARankList.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Attendance/StudentSummary.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Attendance/DailySummary.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Eligibility/Student.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Eligibility/StudentList.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Food_Service/Accounts.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Food_Service/Statements.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Food_Service/DailyMenus.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Food_Service/MenuItems.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Resources/Redirect.php&to=doc', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (0, 'Resources/Redirect.php&to=videohelp', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (0, 'Resources/Redirect.php&to=forums', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (0, 'Resources/Redirect.php&to=translate', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Custom/MyReport.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Custom/CreateParents.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Custom/NotifyParents.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Custom/AttendanceSummary.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Discipline/MakeReferral.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Discipline/Referrals.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Discipline/CategoryBreakdown.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Discipline/CategoryBreakdownTime.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Discipline/StudentFieldBreakdown.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Discipline/ReferralLog.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Discipline/DisciplineForm.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Discipline/ReferralForm.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (2, 'Discipline/MakeReferral.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (2, 'Discipline/Referrals.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (2, 'Grades/GradebookBreakdown.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Scheduling/MasterScheduleReport.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/DatabaseBackup.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/PortalPolls.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'School_Setup/Configuration.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/StudentFees.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/StudentPayments.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/MassAssignFees.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/MassAssignPayments.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/StudentBalances.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/DailyTransactions.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/Statements.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Student_Billing/Fees.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (0, 'Student_Billing/StudentFees.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Student_Billing/StudentPayments.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Student_Billing/DailyTransactions.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (0, 'Student_Billing/Statements.php&_ROSARIO_PDF', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Student_Billing/StudentFees.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Student_Billing/StudentPayments.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Student_Billing/DailyTransactions.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (3, 'Student_Billing/Statements.php&_ROSARIO_PDF', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (1, 'Students/StudentBreakdown.php', 'Y', 'Y');


--
-- Data for Name: program_config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO program_config VALUES (2013, 1, 'eligibility', 'START_DAY', '1');
INSERT INTO program_config VALUES (2013, 1, 'eligibility', 'START_HOUR', '23');
INSERT INTO program_config VALUES (2013, 1, 'eligibility', 'START_MINUTE', '30');
INSERT INTO program_config VALUES (2013, 1, 'eligibility', 'START_M', 'PM');
INSERT INTO program_config VALUES (2013, 1, 'eligibility', 'END_DAY', '5');
INSERT INTO program_config VALUES (2013, 1, 'eligibility', 'END_HOUR', '23');
INSERT INTO program_config VALUES (2013, 1, 'eligibility', 'END_MINUTE', '30');
INSERT INTO program_config VALUES (2013, 1, 'eligibility', 'END_M', 'PM');
INSERT INTO program_config VALUES (2013, 1, 'attendance', 'ATTENDANCE_EDIT_DAYS_BEFORE', NULL);
INSERT INTO program_config VALUES (2013, 1, 'attendance', 'ATTENDANCE_EDIT_DAYS_AFTER', NULL);
INSERT INTO program_config VALUES (2013, 1, 'grades', 'GRADES_DOES_LETTER_PERCENT', '0');
INSERT INTO program_config VALUES (2013, 1, 'grades', 'GRADES_HIDE_NON_ATTENDANCE_COMMENT', NULL);
INSERT INTO program_config VALUES (2013, 1, 'grades', 'GRADES_TEACHER_ALLOW_EDIT', NULL);
INSERT INTO program_config VALUES (2013, 1, 'grades', 'GRADES_DO_STATS_STUDENTS_PARENTS', NULL);
INSERT INTO program_config VALUES (2013, 1, 'grades', 'GRADES_DO_STATS_ADMIN_TEACHERS', 'Y');
INSERT INTO program_config VALUES (2013, 1, 'students', 'STUDENTS_USE_BUS', 'Y');
INSERT INTO program_config VALUES (2013, 1, 'students', 'STUDENTS_USE_CONTACT', 'Y');
INSERT INTO program_config VALUES (2013, 1, 'students', 'STUDENTS_SEMESTER_COMMENTS', NULL);
INSERT INTO program_config VALUES (2013, 1, 'moodle', 'MOODLE_URL', NULL);
INSERT INTO program_config VALUES (2013, 1, 'moodle', 'MOODLE_TOKEN', NULL);
INSERT INTO program_config VALUES (2013, 1, 'moodle', 'MOODLE_PARENT_ROLE_ID', NULL);
INSERT INTO program_config VALUES (2013, 1, 'moodle', 'ROSARIO_STUDENTS_EMAIL_FIELD_ID', NULL);


--
-- Data for Name: program_user_config; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: report_card_comment_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: report_card_comment_code_scales; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: report_card_comment_codes; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: report_card_comments; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO report_card_comments VALUES (1, 2013, 1, NULL, NULL, NULL, 1, '^n Fails to Meet Course Requirements');
INSERT INTO report_card_comments VALUES (2, 2013, 1, NULL, NULL, NULL, 2, '^n Comes to ^s Class Unprepared');
INSERT INTO report_card_comments VALUES (3, 2013, 1, NULL, NULL, NULL, 3, '^n Exerts Positive Influence in Class');


--
-- Data for Name: report_card_grade_scales; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO report_card_grade_scales VALUES (1, 2013, 1, 'Main', NULL, NULL, NULL, 1, NULL, 4, NULL);


--
-- Data for Name: report_card_grades; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO report_card_grades VALUES (1, 2013, 1, 'A+', 1, 4.00, 97, 'Consistently superior', 1, NULL);
INSERT INTO report_card_grades VALUES (2, 2013, 1, 'A', 2, 4.00, 93, 'Superior', 1, NULL);
INSERT INTO report_card_grades VALUES (3, 2013, 1, 'A-', 3, 3.75, 90, NULL, 1, NULL);
INSERT INTO report_card_grades VALUES (4, 2013, 1, 'B+', 4, 3.50, 87, NULL, 1, NULL);
INSERT INTO report_card_grades VALUES (5, 2013, 1, 'B', 5, 3.00, 83, 'Above average', 1, NULL);
INSERT INTO report_card_grades VALUES (6, 2013, 1, 'B-', 6, 2.75, 80, NULL, 1, NULL);
INSERT INTO report_card_grades VALUES (7, 2013, 1, 'C+', 7, 2.50, 77, NULL, 1, NULL);
INSERT INTO report_card_grades VALUES (8, 2013, 1, 'C', 8, 2.00, 73, 'Average', 1, NULL);
INSERT INTO report_card_grades VALUES (9, 2013, 1, 'C-', 9, 1.75, 70, NULL, 1, NULL);
INSERT INTO report_card_grades VALUES (10, 2013, 1, 'D+', 10, 1.50, 67, NULL, 1, NULL);
INSERT INTO report_card_grades VALUES (11, 2013, 1, 'D', 11, 1.00, 63, 'Below average', 1, NULL);
INSERT INTO report_card_grades VALUES (12, 2013, 1, 'D-', 12, 0.75, 60, NULL, 1, NULL);
INSERT INTO report_card_grades VALUES (13, 2013, 1, 'F', 13, 0.00, 0, 'Failing', 1, NULL);
INSERT INTO report_card_grades VALUES (14, 2013, 1, 'I', 14, 0.00, 0, 'Incomplete', 1, NULL);
INSERT INTO report_card_grades VALUES (15, 2013, 1, 'N/A', 15, 0.00, NULL, NULL, 1, NULL);


--
-- Data for Name: schedule; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: schedule_requests; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: school_gradelevels; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO school_gradelevels VALUES (1, 1, 'KG', 'Kindergarten', 2, 1);
INSERT INTO school_gradelevels VALUES (2, 1, '01', '1st', 3, 2);
INSERT INTO school_gradelevels VALUES (3, 1, '02', '2nd', 4, 3);
INSERT INTO school_gradelevels VALUES (4, 1, '03', '3rd', 5, 4);
INSERT INTO school_gradelevels VALUES (5, 1, '04', '4th', 6, 5);
INSERT INTO school_gradelevels VALUES (6, 1, '05', '5th', 7, 6);
INSERT INTO school_gradelevels VALUES (7, 1, '06', '6th', 8, 7);
INSERT INTO school_gradelevels VALUES (8, 1, '07', '7th', 9, 8);
INSERT INTO school_gradelevels VALUES (9, 1, '08', '8th', NULL, 9);


--
-- Data for Name: school_marking_periods; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO school_marking_periods VALUES (1, 2013, 'FY', 1, NULL, 'Full Year', 'FY', 1, '2013-08-21', '2014-06-05', NULL, NULL, NULL, NULL, NULL);
INSERT INTO school_marking_periods VALUES (2, 2013, 'SEM', 1, 1, 'Semester 1', 'S1', 1, '2013-08-21', '2014-01-06', '2014-01-05', '2014-01-06', NULL, NULL, NULL);
INSERT INTO school_marking_periods VALUES (3, 2013, 'SEM', 1, 1, 'Semester 2', 'S2', 2, '2014-01-07', '2014-06-05', '2014-06-04', '2014-06-05', NULL, NULL, NULL);
INSERT INTO school_marking_periods VALUES (4, 2013, 'QTR', 1, 2, 'Quarter 1', 'Q1', 1, '2013-08-21', '2013-10-10', '2013-10-09', '2013-10-10', 'Y', 'Y', NULL);
INSERT INTO school_marking_periods VALUES (5, 2013, 'QTR', 1, 2, 'Quarter 2', 'Q2', 2, '2013-10-11', '2014-01-06', '2014-01-05', '2014-01-06', 'Y', 'Y', NULL);
INSERT INTO school_marking_periods VALUES (6, 2013, 'QTR', 1, 3, 'Quarter 3', 'Q3', 3, '2014-01-07', '2014-03-10', '2014-03-09', '2014-03-10', 'Y', 'Y', NULL);
INSERT INTO school_marking_periods VALUES (7, 2013, 'QTR', 1, 3, 'Quarter 4', 'Q4', 4, '2014-03-11', '2014-06-05', '2014-06-06', '2014-06-05', 'Y', 'Y', NULL);
INSERT INTO school_marking_periods VALUES (8, 2013, 'PRO', 1, 4, 'Midterm 1', 'M1', 1, '2013-08-21', '2013-09-21', '2013-09-20', '2013-09-21', 'Y', NULL, NULL);
INSERT INTO school_marking_periods VALUES (9, 2013, 'PRO', 1, 5, 'Midterm 2', 'M2', 2, '2013-10-11', '2013-11-11', '2013-11-10', '2013-11-11', 'Y', NULL, NULL);
INSERT INTO school_marking_periods VALUES (10, 2013, 'PRO', 1, 6, 'Midterm 3', 'M3', 3, '2014-01-07', '2014-02-07', '2014-02-06', '2014-02-07', 'Y', NULL, NULL);
INSERT INTO school_marking_periods VALUES (11, 2013, 'PRO', 1, 7, 'Midterm 4', 'M4', 4, '2014-03-11', '2014-04-11', '2014-04-10', '2014-04-11', 'Y', NULL, NULL);


--
-- Data for Name: school_periods; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO school_periods VALUES (1, 2013, 1, 1, 'Full Day', 'FD', 300, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO school_periods VALUES (2, 2013, 1, 2, 'Half Day AM', 'AM', 150, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO school_periods VALUES (3, 2013, 1, 3, 'Half Day PM', 'PM', 150, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO school_periods VALUES (4, 2013, 1, 4, 'Period 1', '01', 0, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO school_periods VALUES (5, 2013, 1, 5, 'Period 2', '02', 0, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO school_periods VALUES (6, 2013, 1, 6, 'Period 3', '03', 0, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO school_periods VALUES (7, 2013, 1, 7, 'Period 4', '04', 0, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO school_periods VALUES (8, 2013, 1, 8, 'Period 5', '05', 0, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO school_periods VALUES (9, 2013, 1, 9, 'Period 6', '06', 0, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO school_periods VALUES (10, 2013, 1, 10, 'Period 7', '07', 0, NULL, NULL, NULL, 'Y', NULL);
INSERT INTO school_periods VALUES (11, 2013, 1, 11, 'Period 8', '08', 0, NULL, NULL, NULL, 'Y', NULL);


--
-- Data for Name: schools; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO schools VALUES (2013, 1, 'Default School', '500 S. Street St.', 'Springfield', 'IL', '62704', NULL, 'Mr. Principal', 'www.rosariosis.org', NULL, NULL, 4, NULL);


--
-- Data for Name: staff; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO staff VALUES (2013, 1, 1, NULL, 'Admin', 'Administrator', 'A', NULL, 'admin', '$6$dc51290a001671c6$97VSmw.Qu9sL6vpctFh62/YIbbR6b3DstJJxPXal2OndrtFszsxmVhdQaV2mJvb6Z38sPACXqDDQ7/uquwadd.', NULL, NULL, 'admin', NULL, ',1,', '2013-09-15 20:09:55', NULL, 1, NULL);
INSERT INTO staff VALUES (2013, 2, 1, NULL, 'Teach', 'Teacher', 'T', NULL, 'teacher', '$6$cf0dc4c40d38891f$FqKT6nlTer3ujAf8CcQi6ABIEtlow0Va2p6HYh.M6eGWUfpgLr/pfrSwdIcTlV1LDxLg52puVETGMCYKL3vOo/', NULL, NULL, 'teacher', NULL, ',1,', NULL, NULL, 2, NULL);
INSERT INTO staff VALUES (2013, 3, 1, NULL, 'Parent', 'Parent', 'P', NULL, 'parent', '$6$947c923597601364$Kgbb0Ey3lYTYnqM66VkFRgJVFDW48cBAfNF7t0CVjokL7drcEFId61whqpLrRI1w0q2J2VPfg86Obaf1tG2Ng1', NULL, NULL, 'parent', NULL, ',1,', NULL, NULL, 3, NULL);


--
-- Data for Name: staff_exceptions; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: staff_field_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO staff_field_categories VALUES (1, 'General Info', 1, NULL, NULL, 'Y', 'Y', 'Y', 'Y');
INSERT INTO staff_field_categories VALUES (2, 'Schedule', 2, NULL, NULL, NULL, 'Y', NULL, NULL);
INSERT INTO staff_field_categories VALUES (3, 'Food Service', 3, NULL, 'Food_Service/User', 'Y', 'Y', NULL, NULL);


--
-- Data for Name: staff_fields; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: student_eligibility_activities; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: student_enrollment; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO student_enrollment VALUES (1, 2013, 1, 1, 7, '2013-09-14', NULL, 3, NULL, 1, 1, 1);


--
-- Data for Name: student_enrollment_codes; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO student_enrollment_codes VALUES (1, 2013, 'Moved from District', 'MOVE', 'Drop', NULL, 1);
INSERT INTO student_enrollment_codes VALUES (2, 2013, 'Expelled', 'EXP', 'Drop', NULL, 2);
INSERT INTO student_enrollment_codes VALUES (3, 2013, 'Beginning of Year', 'EBY', 'Add', 'Y', 3);
INSERT INTO student_enrollment_codes VALUES (4, 2013, 'From Other District', 'OTHER', 'Add', NULL, 4);
INSERT INTO student_enrollment_codes VALUES (5, 2013, 'Transferred in District', 'TRAN', 'Drop', NULL, 5);
INSERT INTO student_enrollment_codes VALUES (6, 2013, 'Transferred in District', 'EMY', 'Add', NULL, 6);


--
-- Data for Name: student_field_categories; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO student_field_categories VALUES (1, 'General Info', 1, NULL, NULL);
INSERT INTO student_field_categories VALUES (3, 'Addresses & Contacts', 2, NULL, NULL);
INSERT INTO student_field_categories VALUES (2, 'Medical', 3, NULL, NULL);
INSERT INTO student_field_categories VALUES (4, 'Comments', 4, NULL, NULL);
INSERT INTO student_field_categories VALUES (5, 'Food Service', 5, NULL, 'Food_Service/Student');


--
-- Data for Name: student_medical; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: student_medical_alerts; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: student_medical_visits; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: student_mp_comments; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: student_mp_stats; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: student_report_card_comments; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: student_report_card_grades; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: students; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO students VALUES (1, 'Student', 'Student', 'S', NULL, 'student', '$6$f03d507b27b8b9ff$WKtYRdFZGNjRKUr4btzq/p90hbKRAyB8HmrZpgpUhbAh.GtOCveXtXt43IaEDZJ31rVUYZ7ID8xPgKkCiRyzZ1', NULL, NULL, 'Male', 'White, Non-Hispanic', 'Bug', NULL, '1996-12-04', 'English', NULL, NULL, NULL, NULL, NULL, NULL);


--
-- Data for Name: students_join_address; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: students_join_people; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: students_join_users; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: templates; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO templates VALUES ('Students/Letters.php', 0, '<p></p>');
INSERT INTO templates VALUES ('Grades/HonorRoll.php', 0, '<br /><br /><br />
<div style="text-align: center;"><span style="font-size: xx-large;"><strong>__SCHOOL_ID__</strong><br /></span><br /><span style="font-size: xx-large;">We hereby recognize<br /><br /></span></div>
<div style="text-align: center;"><span style="font-size: xx-large;"><strong>__FIRST_NAME__ __LAST_NAME__</strong><br /><br /></span></div>
<div style="text-align: center;"><span style="font-size: xx-large;">Who has completed all the academic requirements for <br />Honor Roll</span></div>');
INSERT INTO templates VALUES ('Grades/HonorRollSubject.php', 0, '<div style="text-align: center;">__CLIPART__<br /><br /><strong><span style="font-size: xx-large;">__SCHOOL_ID__<br /></span></strong><br /><span style="font-size: xx-large;">We hereby recognize<br /><br /></span></div>
<div style="text-align: center;"><strong><span style="font-size: xx-large;">__FIRST_NAME__ __LAST_NAME__<br /><br /></span></strong></div>
<div style="text-align: center;"><span style="font-size: xx-large;">Who has completed all the academic requirements for Academic Excellence in <br />__SUBJECT__</span></div>');
INSERT INTO templates VALUES ('Grades/Transcripts.php', 0, 'The Principal undersigned certify:
That __FIRST_NAME__ __LAST_NAME__ attended at this school the following courses corresponding to grade __GRADE_ID__ in year __YEAR__ with the following grades and credit hours.
__BLOCK2__');
INSERT INTO templates VALUES ('Custom/CreateParents.php', 0, 'Dear __PARENT_NAME__,

A parent account for the __SCHOOL_ID__ has been created to access school information and student information for the following students:
__ASSOCIATED_STUDENTS__

Your account credentials are:
Username: __USERNAME__
Password: __PASSWORD__

A link to the SIS website and instructions for access are available on the school''s website__BLOCK2__Dear __PARENT_NAME__,

The following students have been added to your parent account on the SIS:
__ASSOCIATED_STUDENTS__');
INSERT INTO templates VALUES ('Custom/NotifyParents.php', 0, 'Dear __PARENT_NAME__,

A parent account for the __SCHOOL_ID__ has been created to access school information and student information for the following students:
__ASSOCIATED_STUDENTS__

Your account credentials are:
Username: __USERNAME__
Password: __PASSWORD__

A link to the SIS website and instructions for access are available on the school''s website');

--
-- Data for Name: user_profiles; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO user_profiles VALUES (0, 'student', 'Student');
INSERT INTO user_profiles VALUES (1, 'admin', 'Administrator');
INSERT INTO user_profiles VALUES (2, 'teacher', 'Teacher');
INSERT INTO user_profiles VALUES (3, 'parent', 'Parent');


--
-- Name: address_field_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY address_field_categories
    ADD CONSTRAINT address_field_categories_pkey PRIMARY KEY (id);


--
-- Name: address_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY address_fields
    ADD CONSTRAINT address_fields_pkey PRIMARY KEY (id);


--
-- Name: address_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY address
    ADD CONSTRAINT address_pkey PRIMARY KEY (address_id);


--
-- Name: attendance_calendar_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY attendance_calendar
    ADD CONSTRAINT attendance_calendar_pkey PRIMARY KEY (syear, school_id, school_date, calendar_id);


--
-- Name: attendance_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY attendance_codes
    ADD CONSTRAINT attendance_codes_pkey PRIMARY KEY (id);


--
-- Name: attendance_completed_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY attendance_completed
    ADD CONSTRAINT attendance_completed_pkey PRIMARY KEY (staff_id, school_date, period_id, table_name);


--
-- Name: attendance_day_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY attendance_day
    ADD CONSTRAINT attendance_day_pkey PRIMARY KEY (student_id, school_date);


--
-- Name: attendance_period_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY attendance_period
    ADD CONSTRAINT attendance_period_pkey PRIMARY KEY (student_id, school_date, period_id);


--
-- Name: calendar_events_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY calendar_events
    ADD CONSTRAINT calendar_events_pkey PRIMARY KEY (id);


--
-- Name: course_period_school_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY course_period_school_periods
    ADD CONSTRAINT course_period_school_periods_pkey PRIMARY KEY (course_period_id, period_id);


--
-- Name: course_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY course_periods
    ADD CONSTRAINT course_periods_pkey PRIMARY KEY (course_period_id);


--
-- Name: course_subjects_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY course_subjects
    ADD CONSTRAINT course_subjects_pkey PRIMARY KEY (subject_id);


--
-- Name: courses_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY courses
    ADD CONSTRAINT courses_pkey PRIMARY KEY (course_id);


--
-- Name: custom_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY custom_fields
    ADD CONSTRAINT custom_fields_pkey PRIMARY KEY (id);


--
-- Name: custom_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY custom
    ADD CONSTRAINT custom_pkey PRIMARY KEY (student_id);


--
-- Name: eligibility_activities_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY eligibility_activities
    ADD CONSTRAINT eligibility_activities_pkey PRIMARY KEY (id);


--
-- Name: eligibility_completed_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY eligibility_completed
    ADD CONSTRAINT eligibility_completed_pkey PRIMARY KEY (staff_id, school_date, period_id);


--
-- Name: food_service_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY food_service_accounts
    ADD CONSTRAINT food_service_accounts_pkey PRIMARY KEY (account_id);


--
-- Name: food_service_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY food_service_categories
    ADD CONSTRAINT food_service_categories_pkey PRIMARY KEY (category_id);


--
-- Name: food_service_items_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY food_service_items
    ADD CONSTRAINT food_service_items_pkey PRIMARY KEY (item_id);


--
-- Name: food_service_menu_items_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY food_service_menu_items
    ADD CONSTRAINT food_service_menu_items_pkey PRIMARY KEY (menu_item_id);


--
-- Name: food_service_menus_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY food_service_menus
    ADD CONSTRAINT food_service_menus_pkey PRIMARY KEY (menu_id);


--
-- Name: food_service_staff_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY food_service_staff_accounts
    ADD CONSTRAINT food_service_staff_accounts_pkey PRIMARY KEY (staff_id);


--
-- Name: food_service_staff_transaction_items_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY food_service_staff_transaction_items
    ADD CONSTRAINT food_service_staff_transaction_items_pkey PRIMARY KEY (item_id, transaction_id);


--
-- Name: food_service_staff_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY food_service_staff_transactions
    ADD CONSTRAINT food_service_staff_transactions_pkey PRIMARY KEY (transaction_id);


--
-- Name: food_service_student_accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY food_service_student_accounts
    ADD CONSTRAINT food_service_student_accounts_pkey PRIMARY KEY (student_id);


--
-- Name: food_service_transaction_items_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY food_service_transaction_items
    ADD CONSTRAINT food_service_transaction_items_pkey PRIMARY KEY (item_id, transaction_id);


--
-- Name: food_service_transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY food_service_transactions
    ADD CONSTRAINT food_service_transactions_pkey PRIMARY KEY (transaction_id);


--
-- Name: gradebook_assignment_types_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY gradebook_assignment_types
    ADD CONSTRAINT gradebook_assignment_types_pkey PRIMARY KEY (assignment_type_id);


--
-- Name: gradebook_assignments_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY gradebook_assignments
    ADD CONSTRAINT gradebook_assignments_pkey PRIMARY KEY (assignment_id);


--
-- Name: gradebook_grades_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY gradebook_grades
    ADD CONSTRAINT gradebook_grades_pkey PRIMARY KEY (student_id, assignment_id, course_period_id);


--
-- Name: grades_completed_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY grades_completed
    ADD CONSTRAINT grades_completed_pkey PRIMARY KEY (staff_id, marking_period_id, course_period_id);


--
-- Name: history_marking_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY history_marking_periods
    ADD CONSTRAINT history_marking_periods_pkey PRIMARY KEY (marking_period_id);


--
-- Name: lunch_period_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY lunch_period
    ADD CONSTRAINT lunch_period_pkey PRIMARY KEY (student_id, school_date, period_id);


--
-- Name: moodlexrosario_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY moodlexrosario
    ADD CONSTRAINT moodlexrosario_pkey PRIMARY KEY ("column", rosario_id);


--
-- Name: people_field_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY people_field_categories
    ADD CONSTRAINT people_field_categories_pkey PRIMARY KEY (id);


--
-- Name: people_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY people_fields
    ADD CONSTRAINT people_fields_pkey PRIMARY KEY (id);


--
-- Name: people_join_contacts_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY people_join_contacts
    ADD CONSTRAINT people_join_contacts_pkey PRIMARY KEY (id);


--
-- Name: people_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY people
    ADD CONSTRAINT people_pkey PRIMARY KEY (person_id);


--
-- Name: portal_notes_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY portal_notes
    ADD CONSTRAINT portal_notes_pkey PRIMARY KEY (id);


--
-- Name: portal_poll_questions_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY portal_poll_questions
    ADD CONSTRAINT portal_poll_questions_pkey PRIMARY KEY (id);


--
-- Name: profile_exceptions_profile_id_modname_key; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY profile_exceptions
    ADD CONSTRAINT profile_exceptions_profile_id_modname_key UNIQUE (profile_id, modname);


--
-- Name: report_card_comment_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY report_card_comment_categories
    ADD CONSTRAINT report_card_comment_categories_pkey PRIMARY KEY (id);


--
-- Name: report_card_comment_code_scales_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY report_card_comment_code_scales
    ADD CONSTRAINT report_card_comment_code_scales_pkey PRIMARY KEY (id);


--
-- Name: report_card_comment_codes_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY report_card_comment_codes
    ADD CONSTRAINT report_card_comment_codes_pkey PRIMARY KEY (id);


--
-- Name: report_card_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY report_card_comments
    ADD CONSTRAINT report_card_comments_pkey PRIMARY KEY (id);


--
-- Name: report_card_grade_scales_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY report_card_grade_scales
    ADD CONSTRAINT report_card_grade_scales_pkey PRIMARY KEY (id);


--
-- Name: report_card_grades_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY report_card_grades
    ADD CONSTRAINT report_card_grades_pkey PRIMARY KEY (id);


--
-- Name: schedule_requests_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY schedule_requests
    ADD CONSTRAINT schedule_requests_pkey PRIMARY KEY (request_id);


--
-- Name: school_gradelevels_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY school_gradelevels
    ADD CONSTRAINT school_gradelevels_pkey PRIMARY KEY (id);


--
-- Name: school_marking_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY school_marking_periods
    ADD CONSTRAINT school_marking_periods_pkey PRIMARY KEY (marking_period_id);


--
-- Name: school_periods_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY school_periods
    ADD CONSTRAINT school_periods_pkey PRIMARY KEY (period_id);


--
-- Name: schools_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY schools
    ADD CONSTRAINT schools_pkey PRIMARY KEY (id, syear);


--
-- Name: staff_field_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY staff_field_categories
    ADD CONSTRAINT staff_field_categories_pkey PRIMARY KEY (id);


--
-- Name: staff_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY staff_fields
    ADD CONSTRAINT staff_fields_pkey PRIMARY KEY (id);


--
-- Name: staff_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY staff
    ADD CONSTRAINT staff_pkey PRIMARY KEY (staff_id);


--
-- Name: student_enrollment_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY student_enrollment
    ADD CONSTRAINT student_enrollment_pkey PRIMARY KEY (id);


--
-- Name: student_field_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY student_field_categories
    ADD CONSTRAINT student_field_categories_pkey PRIMARY KEY (id);


--
-- Name: student_medical_alerts_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY student_medical_alerts
    ADD CONSTRAINT student_medical_alerts_pkey PRIMARY KEY (id);


--
-- Name: student_medical_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY student_medical
    ADD CONSTRAINT student_medical_pkey PRIMARY KEY (id);


--
-- Name: student_medical_visits_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY student_medical_visits
    ADD CONSTRAINT student_medical_visits_pkey PRIMARY KEY (id);


--
-- Name: student_mp_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY student_mp_comments
    ADD CONSTRAINT student_mp_comments_pkey PRIMARY KEY (student_id, syear, marking_period_id);


--
-- Name: student_mp_stats_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY student_mp_stats
    ADD CONSTRAINT student_mp_stats_pkey PRIMARY KEY (student_id, marking_period_id);


--
-- Name: student_report_card_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY student_report_card_comments
    ADD CONSTRAINT student_report_card_comments_pkey PRIMARY KEY (syear, student_id, course_period_id, marking_period_id, report_card_comment_id);


--
-- Name: student_report_card_grades_id_key; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY student_report_card_grades
    ADD CONSTRAINT student_report_card_grades_id_key UNIQUE (id);


--
-- Name: student_report_card_grades_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY student_report_card_grades
    ADD CONSTRAINT student_report_card_grades_pkey PRIMARY KEY (id);


--
-- Name: students_join_address_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY students_join_address
    ADD CONSTRAINT students_join_address_pkey PRIMARY KEY (id);


--
-- Name: students_join_people_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY students_join_people
    ADD CONSTRAINT students_join_people_pkey PRIMARY KEY (id);


--
-- Name: students_join_users_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY students_join_users
    ADD CONSTRAINT students_join_users_pkey PRIMARY KEY (student_id, staff_id);


--
-- Name: students_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY students
    ADD CONSTRAINT students_pkey PRIMARY KEY (student_id);


--
-- Name: templates_pkey; Type: CONSTRAINT; Schema: public; Owner: rosariosis; Tablespace: 
--

ALTER TABLE ONLY templates
    ADD CONSTRAINT templates_pkey PRIMARY KEY (modname, staff_id);


--
-- Name: address_3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX address_3 ON address USING btree (zipcode, plus4);


--
-- Name: address_4; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX address_4 ON address USING btree (street);


--
-- Name: address_desc_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX address_desc_ind ON address_fields USING btree (id);


--
-- Name: address_desc_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX address_desc_ind2 ON custom_fields USING btree (type);


--
-- Name: address_fields_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX address_fields_ind3 ON custom_fields USING btree (category_id);


--
-- Name: attendance_code_categories_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX attendance_code_categories_ind1 ON attendance_code_categories USING btree (id);


--
-- Name: attendance_code_categories_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX attendance_code_categories_ind2 ON attendance_code_categories USING btree (syear, school_id);


--
-- Name: attendance_codes_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX attendance_codes_ind2 ON attendance_codes USING btree (syear, school_id);


--
-- Name: attendance_codes_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX attendance_codes_ind3 ON attendance_codes USING btree (short_name);


--
-- Name: attendance_period_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX attendance_period_ind1 ON attendance_period USING btree (student_id);


--
-- Name: attendance_period_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX attendance_period_ind2 ON attendance_period USING btree (period_id);


--
-- Name: attendance_period_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX attendance_period_ind3 ON attendance_period USING btree (attendance_code);


--
-- Name: attendance_period_ind4; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX attendance_period_ind4 ON attendance_period USING btree (school_date);


--
-- Name: attendance_period_ind5; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX attendance_period_ind5 ON attendance_period USING btree (attendance_code);


--
-- Name: billing_fees_pkey; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX billing_fees_pkey ON billing_fees USING btree (id);


--
-- Name: billing_payments_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX billing_payments_ind1 ON billing_payments USING btree (student_id);


--
-- Name: billing_payments_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX billing_payments_ind2 ON billing_payments USING btree (amount);


--
-- Name: billing_payments_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX billing_payments_ind3 ON billing_payments USING btree (refunded_payment_id);


--
-- Name: billing_payments_pkey; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX billing_payments_pkey ON billing_payments USING btree (id);


--
-- Name: course_periods_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX course_periods_ind1 ON course_periods USING btree (syear);


--
-- Name: course_periods_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX course_periods_ind2 ON course_periods USING btree (course_id, syear, school_id);


--
-- Name: course_periods_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX course_periods_ind3 ON course_periods USING btree (course_period_id);


--
-- Name: course_periods_ind5; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX course_periods_ind5 ON course_periods USING btree (parent_id);


--
-- Name: course_subjects_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX course_subjects_ind1 ON course_subjects USING btree (syear, school_id, subject_id);


--
-- Name: courses_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX courses_ind1 ON courses USING btree (course_id, syear);


--
-- Name: courses_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX courses_ind2 ON courses USING btree (subject_id);


--
-- Name: custom_desc_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX custom_desc_ind ON custom_fields USING btree (id);


--
-- Name: custom_desc_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX custom_desc_ind2 ON custom_fields USING btree (type);


--
-- Name: custom_fields_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX custom_fields_ind3 ON custom_fields USING btree (category_id);


--
-- Name: custom_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX custom_ind ON custom USING btree (student_id);


--
-- Name: discipline_field_usage_pkey; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX discipline_field_usage_pkey ON discipline_field_usage USING btree (id);


--
-- Name: discipline_fields_pkey; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX discipline_fields_pkey ON discipline_fields USING btree (id);


--
-- Name: discipline_referrals_pkey; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX discipline_referrals_pkey ON discipline_referrals USING btree (id);


--
-- Name: eligibility_activities_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX eligibility_activities_ind1 ON eligibility_activities USING btree (school_id, syear);


--
-- Name: eligibility_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX eligibility_ind1 ON eligibility USING btree (student_id, course_period_id, school_date);


--
-- Name: food_service_categories_title; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX food_service_categories_title ON food_service_categories USING btree (school_id, menu_id, title);


--
-- Name: food_service_items_short_name; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX food_service_items_short_name ON food_service_items USING btree (school_id, short_name);


--
-- Name: food_service_menus_title; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX food_service_menus_title ON food_service_menus USING btree (school_id, title);


--
-- Name: food_service_staff_transaction_items_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX food_service_staff_transaction_items_ind1 ON food_service_staff_transaction_items USING btree (transaction_id);


--
-- Name: food_service_transaction_items_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX food_service_transaction_items_ind1 ON food_service_transaction_items USING btree (transaction_id);


--
-- Name: gradebook_assignment_types_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX gradebook_assignment_types_ind1 ON gradebook_assignments USING btree (staff_id, course_id);


--
-- Name: gradebook_assignments_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX gradebook_assignments_ind1 ON gradebook_assignments USING btree (staff_id, marking_period_id);


--
-- Name: gradebook_assignments_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX gradebook_assignments_ind2 ON gradebook_assignments USING btree (course_id, course_period_id);


--
-- Name: gradebook_assignments_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX gradebook_assignments_ind3 ON gradebook_assignments USING btree (assignment_type_id);


--
-- Name: gradebook_grades_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX gradebook_grades_ind1 ON gradebook_grades USING btree (assignment_id);


--
-- Name: history_marking_period_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX history_marking_period_ind1 ON history_marking_periods USING btree (school_id);


--
-- Name: history_marking_period_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX history_marking_period_ind2 ON history_marking_periods USING btree (syear);


--
-- Name: history_marking_period_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX history_marking_period_ind3 ON history_marking_periods USING btree (mp_type);


--
-- Name: lunch_period_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX lunch_period_ind1 ON lunch_period USING btree (student_id);


--
-- Name: lunch_period_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX lunch_period_ind2 ON lunch_period USING btree (period_id);


--
-- Name: lunch_period_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX lunch_period_ind3 ON lunch_period USING btree (attendance_code);


--
-- Name: lunch_period_ind4; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX lunch_period_ind4 ON lunch_period USING btree (school_date);


--
-- Name: lunch_period_ind5; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX lunch_period_ind5 ON lunch_period USING btree (attendance_code);


--
-- Name: name; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX name ON students USING btree (last_name, first_name, middle_name);


--
-- Name: people_1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX people_1 ON people USING btree (last_name, first_name);


--
-- Name: people_3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX people_3 ON people USING btree (person_id, last_name, first_name, middle_name);


--
-- Name: people_desc_ind; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX people_desc_ind ON people_fields USING btree (id);


--
-- Name: people_desc_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX people_desc_ind2 ON custom_fields USING btree (type);


--
-- Name: people_fields_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX people_fields_ind3 ON custom_fields USING btree (category_id);


--
-- Name: people_join_contacts_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX people_join_contacts_ind1 ON people_join_contacts USING btree (person_id);


--
-- Name: program_config_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX program_config_ind1 ON program_config USING btree (program, school_id, syear);


--
-- Name: program_user_config_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX program_user_config_ind1 ON program_user_config USING btree (user_id, program);


--
-- Name: relations_meets_2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX relations_meets_2 ON students_join_people USING btree (person_id);


--
-- Name: relations_meets_5; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX relations_meets_5 ON students_join_people USING btree (id);


--
-- Name: relations_meets_6; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX relations_meets_6 ON students_join_people USING btree (custody, emergency);


--
-- Name: report_card_comment_categories_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX report_card_comment_categories_ind1 ON report_card_comment_categories USING btree (syear, school_id);


--
-- Name: report_card_comment_codes_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX report_card_comment_codes_ind1 ON report_card_comment_codes USING btree (school_id);


--
-- Name: report_card_comments_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX report_card_comments_ind1 ON report_card_comments USING btree (syear, school_id);


--
-- Name: report_card_grades_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX report_card_grades_ind1 ON report_card_grades USING btree (syear, school_id);


--
-- Name: schedule_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schedule_ind1 ON schedule USING btree (course_id);


--
-- Name: schedule_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schedule_ind2 ON schedule USING btree (course_period_id);


--
-- Name: schedule_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schedule_ind3 ON schedule USING btree (student_id, marking_period_id, start_date, end_date);


--
-- Name: schedule_ind4; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schedule_ind4 ON schedule USING btree (syear, school_id);


--
-- Name: schedule_requests_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schedule_requests_ind1 ON schedule_requests USING btree (student_id, course_id, syear, school_id);


--
-- Name: schedule_requests_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schedule_requests_ind2 ON schedule_requests USING btree (syear, school_id);


--
-- Name: schedule_requests_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schedule_requests_ind3 ON schedule_requests USING btree (course_id, syear, school_id);


--
-- Name: schedule_requests_ind4; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schedule_requests_ind4 ON schedule_requests USING btree (with_teacher_id);


--
-- Name: schedule_requests_ind5; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schedule_requests_ind5 ON schedule_requests USING btree (not_teacher_id);


--
-- Name: schedule_requests_ind6; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schedule_requests_ind6 ON schedule_requests USING btree (with_period_id);


--
-- Name: schedule_requests_ind7; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schedule_requests_ind7 ON schedule_requests USING btree (not_period_id);


--
-- Name: schedule_requests_ind8; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schedule_requests_ind8 ON schedule_requests USING btree (request_id);


--
-- Name: school_gradelevels_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX school_gradelevels_ind1 ON school_gradelevels USING btree (school_id);


--
-- Name: school_marking_periods_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX school_marking_periods_ind1 ON school_marking_periods USING btree (parent_id);


--
-- Name: school_marking_periods_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX school_marking_periods_ind2 ON school_marking_periods USING btree (syear, school_id, start_date, end_date);


--
-- Name: school_periods_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX school_periods_ind1 ON school_periods USING btree (period_id, syear);


--
-- Name: schools_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX schools_ind1 ON schools USING btree (syear);


--
-- Name: staff_barcode; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX staff_barcode ON food_service_staff_accounts USING btree (barcode);


--
-- Name: staff_desc_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX staff_desc_ind1 ON staff_fields USING btree (id);


--
-- Name: staff_desc_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX staff_desc_ind2 ON staff_fields USING btree (type);


--
-- Name: staff_fields_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX staff_fields_ind3 ON staff_fields USING btree (category_id);


--
-- Name: staff_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX staff_ind1 ON staff USING btree (staff_id, syear);


--
-- Name: staff_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX staff_ind2 ON staff USING btree (last_name, first_name);


--
-- Name: staff_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX staff_ind3 ON staff USING btree (schools);


--
-- Name: staff_ind4; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX staff_ind4 ON staff USING btree (username, syear);


--
-- Name: stu_addr_meets_2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX stu_addr_meets_2 ON students_join_address USING btree (address_id);


--
-- Name: stu_addr_meets_3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX stu_addr_meets_3 ON students_join_address USING btree (primary_residence);


--
-- Name: stu_addr_meets_4; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX stu_addr_meets_4 ON students_join_address USING btree (legal_residence);


--
-- Name: student_eligibility_activities_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_eligibility_activities_ind1 ON student_eligibility_activities USING btree (student_id);


--
-- Name: student_enrollment_1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_enrollment_1 ON student_enrollment USING btree (student_id, enrollment_code);


--
-- Name: student_enrollment_2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_enrollment_2 ON student_enrollment USING btree (grade_id);


--
-- Name: student_enrollment_3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_enrollment_3 ON student_enrollment USING btree (syear, student_id, school_id, grade_id);


--
-- Name: student_enrollment_6; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_enrollment_6 ON student_enrollment USING btree (start_date, end_date);


--
-- Name: student_enrollment_7; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_enrollment_7 ON student_enrollment USING btree (school_id);


--
-- Name: student_medical_alerts_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_medical_alerts_ind1 ON student_medical_alerts USING btree (student_id);


--
-- Name: student_medical_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_medical_ind1 ON student_medical USING btree (student_id);


--
-- Name: student_medical_visits_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_medical_visits_ind1 ON student_medical_visits USING btree (student_id);


--
-- Name: student_report_card_comments_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_report_card_comments_ind1 ON student_report_card_comments USING btree (school_id);


--
-- Name: student_report_card_grades_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_report_card_grades_ind1 ON student_report_card_grades USING btree (school_id);


--
-- Name: student_report_card_grades_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_report_card_grades_ind2 ON student_report_card_grades USING btree (student_id);


--
-- Name: student_report_card_grades_ind3; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_report_card_grades_ind3 ON student_report_card_grades USING btree (course_period_id);


--
-- Name: student_report_card_grades_ind4; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX student_report_card_grades_ind4 ON student_report_card_grades USING btree (marking_period_id);


--
-- Name: students_barcode; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX students_barcode ON food_service_student_accounts USING btree (barcode);


--
-- Name: students_ind4; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX students_ind4 ON students USING btree (username);


--
-- Name: students_join_address_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX students_join_address_ind1 ON students_join_address USING btree (student_id);


--
-- Name: students_join_address_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX students_join_address_ind2 ON students_join_address USING btree (id, student_id, address_id);


--
-- Name: students_join_people_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX students_join_people_ind1 ON students_join_people USING btree (student_id);


--
-- Name: srcg_mp_stats_update; Type: TRIGGER; Schema: public; Owner: rosariosis
--

CREATE TRIGGER srcg_mp_stats_update AFTER INSERT OR DELETE OR UPDATE ON student_report_card_grades FOR EACH ROW EXECUTE PROCEDURE t_update_mp_stats();



--
-- PostgreSQL database dump complete
--
