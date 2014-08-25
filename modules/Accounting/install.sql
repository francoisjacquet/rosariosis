--
-- Name: accounting_incomes; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE accounting_incomes (
    assigned_date date,
    comments character varying(255),
    id numeric,
    title character varying(255),
    amount numeric,
    school_id numeric,
    syear numeric
);




--
-- Name: accounting_incomes_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE accounting_incomes_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: accounting_incomes_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('accounting_incomes_seq', 1, false);




--
-- Name: accounting_salaries; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE accounting_salaries (
    staff_id numeric NOT NULL,
    assigned_date date,
    due_date date,
    comments character varying(255),
    id numeric,
    title character varying(255),
    amount numeric,
    school_id numeric,
    syear numeric
);




--
-- Name: accounting_salaries_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE accounting_salaries_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: accounting_salaries_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('accounting_salaries_seq', 1, false);




--
-- Name: accounting_payments; Type: TABLE; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE TABLE accounting_payments (
    id numeric NOT NULL,
    syear numeric NOT NULL,
    school_id numeric NOT NULL,
    staff_id numeric,
    amount numeric NOT NULL,
    payment_date date,
    comments character varying(255)
);




--
-- Name: accounting_payments_seq; Type: SEQUENCE; Schema: public; Owner: rosariosis
--

CREATE SEQUENCE accounting_payments_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;




--
-- Name: accounting_payments_seq; Type: SEQUENCE SET; Schema: public; Owner: rosariosis
--

SELECT pg_catalog.setval('accounting_payments_seq', 1, false);


--
-- Data for Name: accounting_incomes; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: accounting_salaries; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: accounting_payments; Type: TABLE DATA; Schema: public; Owner: rosariosis
--



--
-- Data for Name: profile_exceptions; Type: TABLE DATA; Schema: public; Owner: rosariosis
--

INSERT INTO profile_exceptions VALUES (1, 'Accounting/DailyTransactions.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Accounting/Expenses.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Accounting/Incomes.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Accounting/Salaries.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Accounting/StaffBalances.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Accounting/StaffPayments.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (1, 'Accounting/Statements.php', 'Y', 'Y');
INSERT INTO profile_exceptions VALUES (2, 'Accounting/Salaries.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Accounting/StaffPayments.php', 'Y', NULL);
INSERT INTO profile_exceptions VALUES (2, 'Accounting/Statements.php&_ROSARIO_PDF', 'Y', NULL);


--
-- Name: accounting_incomes_pkey; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX accounting_incomes_pkey ON accounting_incomes USING btree (id);


--
-- Name: accounting_salaries_pkey; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE UNIQUE INDEX accounting_salaries_pkey ON accounting_salaries USING btree (id);


--
-- Name: accounting_payments_ind1; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX accounting_payments_ind1 ON accounting_payments USING btree (staff_id);


--
-- Name: accounting_payments_ind2; Type: INDEX; Schema: public; Owner: rosariosis; Tablespace: 
--

CREATE INDEX accounting_payments_ind2 ON accounting_payments USING btree (amount);

