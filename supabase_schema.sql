-- ==============================================================================
-- EDUCARDSMART SCHOOL & RFID POS MANAGEMENT
-- Database Schema for Supabase (PostgreSQL)
-- ==============================================================================

-- 1. Enable UUID Extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ==============================================================================
-- 2. TABLE DEFINITIONS
-- ==============================================================================

-- 2.1 Orang Tua Siswa (parents)
CREATE TABLE IF NOT EXISTS public.parents (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    full_name TEXT NOT NULL,
    phone_number TEXT,
    email TEXT,
    address TEXT,
    fcm_token TEXT,
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 2.2 Data Siswa (students)
CREATE TABLE IF NOT EXISTS public.students (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    parent_id UUID REFERENCES public.parents(id) ON DELETE SET NULL,
    nisn TEXT UNIQUE NOT NULL,
    full_name TEXT NOT NULL,
    gender VARCHAR(1) DEFAULT 'L' CHECK (gender IN ('L', 'P')),
    class_name TEXT NOT NULL,
    school_name TEXT DEFAULT 'SMA Nusantara 1',
    photo_url TEXT,
    status VARCHAR(20) DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE', 'GRADUATED', 'SUSPENDED')),
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 2.3 Smart Card RFID (cards)
CREATE TABLE IF NOT EXISTS public.cards (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id UUID UNIQUE NOT NULL REFERENCES public.students(id) ON DELETE CASCADE,
    card_uid TEXT UNIQUE NOT NULL,
    pin_hash TEXT,
    status VARCHAR(20) DEFAULT 'ACTIVE' CHECK (status IN ('ACTIVE', 'BLOCKED', 'LOST', 'SUSPENDED')),
    balance NUMERIC(14, 2) DEFAULT 0 CHECK (balance >= 0),
    daily_spending_limit NUMERIC(14, 2) DEFAULT 25000 CHECK (daily_spending_limit >= 0),
    blocked_reason TEXT,
    issued_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()),
    last_tapped_at TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 2.4 Transaksi POS Kantin (canteen_transactions)
CREATE TABLE IF NOT EXISTS public.canteen_transactions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    receipt_number TEXT UNIQUE NOT NULL,
    student_id UUID NOT NULL REFERENCES public.students(id) ON DELETE CASCADE,
    card_id UUID NOT NULL REFERENCES public.cards(id) ON DELETE CASCADE,
    tenant_name TEXT NOT NULL DEFAULT 'Kantin',
    menu_name TEXT NOT NULL DEFAULT 'Pembelian Kasir POS',
    category VARCHAR(20) DEFAULT 'FOOD' CHECK (category IN ('FOOD', 'DRINK', 'SNACK', 'STATIONERY', 'OTHER')),
    amount NUMERIC(14, 2) NOT NULL CHECK (amount > 0),
    balance_before NUMERIC(14, 2) DEFAULT 0,
    balance_after NUMERIC(14, 2) DEFAULT 0,
    status VARCHAR(20) DEFAULT 'SUCCESS' CHECK (status IN ('SUCCESS', 'FAILED', 'REFUNDED')),
    payment_method VARCHAR(20) DEFAULT 'RFID_CARD',
    notes TEXT,
    timestamp TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 2.5 Presensi Gerbang (attendances)
CREATE TABLE IF NOT EXISTS public.attendances (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id UUID NOT NULL REFERENCES public.students(id) ON DELETE CASCADE,
    card_id UUID NOT NULL REFERENCES public.cards(id) ON DELETE CASCADE,
    date DATE NOT NULL DEFAULT CURRENT_DATE,
    tap_in_time TIMESTAMPTZ,
    tap_out_time TIMESTAMPTZ,
    gate_name TEXT DEFAULT 'Gerbang Utama',
    status VARCHAR(20) DEFAULT 'HADIR' CHECK (status IN ('HADIR', 'TERLAMBAT', 'PULANG', 'IZIN', 'SAKIT', 'ALPA')),
    is_late BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    UNIQUE (student_id, date)
);

-- 2.6 Tagihan SPP (spp_bills)
CREATE TABLE IF NOT EXISTS public.spp_bills (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id UUID NOT NULL REFERENCES public.students(id) ON DELETE CASCADE,
    month SMALLINT NOT NULL CHECK (month BETWEEN 1 AND 12),
    year SMALLINT NOT NULL,
    amount NUMERIC(14, 2) NOT NULL CHECK (amount > 0),
    status VARCHAR(20) DEFAULT 'UNPAID' CHECK (status IN ('UNPAID', 'PAID', 'OVERDUE', 'CANCELLED')),
    due_date DATE NOT NULL,
    paid_at TIMESTAMPTZ,
    receipt_number TEXT UNIQUE,
    paid_via VARCHAR(20) CHECK (paid_via IN ('RFID_BALANCE', 'CASH', 'TRANSFER')),
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL,
    UNIQUE (student_id, year, month)
);

-- 2.7 Tabungan Siswa (student_savings)
CREATE TABLE IF NOT EXISTS public.student_savings (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    student_id UUID NOT NULL REFERENCES public.students(id) ON DELETE CASCADE,
    type VARCHAR(20) NOT NULL CHECK (type IN ('DEPOSIT', 'WITHDRAWAL')),
    amount NUMERIC(14, 2) NOT NULL CHECK (amount > 0),
    balance_after NUMERIC(14, 2) NOT NULL CHECK (balance_after >= 0),
    note TEXT,
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 2.8 Top-Up Kartu RFID (card_topups)
CREATE TABLE IF NOT EXISTS public.card_topups (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    card_id UUID NOT NULL REFERENCES public.cards(id) ON DELETE CASCADE,
    amount NUMERIC(14, 2) NOT NULL CHECK (amount > 0),
    balance_before NUMERIC(14, 2) DEFAULT 0,
    balance_after NUMERIC(14, 2) NOT NULL,
    method VARCHAR(20) DEFAULT 'CASH' CHECK (method IN ('CASH', 'TRANSFER', 'EDC')),
    receipt_number TEXT UNIQUE NOT NULL,
    notes TEXT,
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 2.9 Log Audit Forensik & Keamanan (security_audit_logs)
CREATE TABLE IF NOT EXISTS public.security_audit_logs (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    action VARCHAR(80) NOT NULL,
    severity VARCHAR(20) DEFAULT 'INFO' CHECK (severity IN ('INFO', 'NOTICE', 'WARNING', 'CRITICAL')),
    status VARCHAR(20) DEFAULT 'SUCCESS' CHECK (status IN ('SUCCESS', 'FAILED', 'BLOCKED')),
    ip_address VARCHAR(45),
    user_agent TEXT,
    target_type VARCHAR(80),
    target_id VARCHAR(80),
    details JSONB,
    created_at TIMESTAMPTZ DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- ==============================================================================
-- 3. PERFORMANCE INDEXES
-- ==============================================================================
CREATE INDEX IF NOT EXISTS idx_students_nisn ON public.students (nisn);
CREATE INDEX IF NOT EXISTS idx_cards_uid ON public.cards (card_uid);
CREATE INDEX IF NOT EXISTS idx_cards_student ON public.cards (student_id);
CREATE INDEX IF NOT EXISTS idx_canteen_date ON public.canteen_transactions (created_at);
CREATE INDEX IF NOT EXISTS idx_canteen_receipt ON public.canteen_transactions (receipt_number);
CREATE INDEX IF NOT EXISTS idx_attendances_date_student ON public.attendances (date, student_id);
CREATE INDEX IF NOT EXISTS idx_spp_status ON public.spp_bills (status);
CREATE INDEX IF NOT EXISTS idx_audit_severity ON public.security_audit_logs (severity);

-- ==============================================================================
-- 4. ROW LEVEL SECURITY (RLS) POLICIES
-- ==============================================================================
ALTER TABLE public.parents ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.students ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.cards ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.canteen_transactions ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.attendances ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.spp_bills ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.student_savings ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.card_topups ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.security_audit_logs ENABLE ROW LEVEL SECURITY;

-- Allow read & write access with API key (anon & authenticated)
DO $$
DECLARE
    tbl text;
BEGIN
    FOR tbl IN SELECT tablename FROM pg_tables WHERE schemaname = 'public' LOOP
        EXECUTE format('DROP POLICY IF EXISTS "Public access policy" ON public.%I', tbl);
        EXECUTE format('CREATE POLICY "Public access policy" ON public.%I FOR ALL USING (true) WITH CHECK (true)', tbl);
    END LOOP;
END $$;

-- ==============================================================================
-- 5. ATOMIC STORED PROCEDURES (Zero Race Condition)
-- ==============================================================================

-- Stored Procedure: Atomic POS Canteen Payment with Pessimistic Lock
CREATE OR REPLACE FUNCTION public.execute_canteen_payment(
    p_card_uid TEXT,
    p_amount NUMERIC,
    p_tenant_name TEXT,
    p_menu_name TEXT,
    p_receipt_number TEXT
) RETURNS JSONB AS $$
DECLARE
    v_card RECORD;
    v_new_balance NUMERIC;
BEGIN
    -- Acquire pessimistic lock on card row
    SELECT * INTO v_card
    FROM public.cards
    WHERE card_uid = UPPER(p_card_uid)
    FOR UPDATE;

    IF NOT FOUND THEN
        RETURN jsonb_build_object('success', false, 'message', 'Kartu RFID tidak ditemukan.');
    END IF;

    IF v_card.status <> 'ACTIVE' THEN
        RETURN jsonb_build_object('success', false, 'message', 'Kartu berstatus ' || v_card.status || '. Ditolak.');
    END IF;

    IF v_card.balance < p_amount THEN
        RETURN jsonb_build_object('success', false, 'message', 'Saldo tidak mencukupi.');
    END IF;

    v_new_balance := v_card.balance - p_amount;

    -- Update balance
    UPDATE public.cards
    SET balance = v_new_balance,
        last_tapped_at = timezone('utc'::text, now()),
        updated_at = timezone('utc'::text, now())
    WHERE id = v_card.id;

    -- Record transaction
    INSERT INTO public.canteen_transactions (
        receipt_number,
        student_id,
        card_id,
        tenant_name,
        menu_name,
        amount,
        balance_before,
        balance_after,
        status
    ) VALUES (
        p_receipt_number,
        v_card.student_id,
        v_card.id,
        p_tenant_name,
        p_menu_name,
        p_amount,
        v_card.balance,
        v_new_balance,
        'SUCCESS'
    );

    RETURN jsonb_build_object(
        'success', true,
        'receipt_number', p_receipt_number,
        'balance_before', v_card.balance,
        'balance_after', v_new_balance
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;
