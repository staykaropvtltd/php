-- 001_init.sql
-- Supabase PostgreSQL schema and seed data for AIT Next Gen Pro app

-- Extensions
create extension if not exists pgcrypto;
create extension if not exists citext;

-- Enums
do $$
begin
    if not exists (select 1 from pg_type where typname = 'user_role') then
        create type user_role as enum ('admin', 'client');
    end if;

    if not exists (select 1 from pg_type where typname = 'contact_status') then
        create type contact_status as enum ('new', 'in_progress', 'closed');
    end if;

    if not exists (select 1 from pg_type where typname = 'service_request_status') then
        create type service_request_status as enum (
            'submitted',
            'in_review',
            'quoted',
            'approved',
            'in_progress',
            'completed',
            'rejected'
        );
    end if;
end
$$;

-- 1) Users
create table if not exists users (
    id uuid primary key default gen_random_uuid(),
    username citext not null unique,
    full_name text not null,
    email citext unique,
    password_hash text not null,
    role user_role not null,
    is_active boolean not null default true,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

-- 2) Remember Tokens
create table if not exists remember_tokens (
    id uuid primary key default gen_random_uuid(),
    user_id uuid not null references users (id) on delete cascade,
    selector text not null unique,
    token_hash text not null,
    expires_at timestamptz not null,
    created_at timestamptz not null default now(),
    revoked_at timestamptz
);

-- 3) Site Settings (KV)
create table if not exists site_settings (
    key text primary key,
    value text not null,
    updated_at timestamptz not null default now(),
    updated_by uuid references users (id)
);

-- 4) Pages
create table if not exists pages (
    id uuid primary key default gen_random_uuid(),
    slug text not null unique,
    title text not null,
    is_published boolean not null default true,
    updated_by uuid references users (id),
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

-- 4b) Page Content
create table if not exists page_content (
    page_id uuid primary key references pages (id) on delete cascade,
    heading text,
    subheading text,
    content text,
    highlight text,
    updated_at timestamptz not null default now(),
    updated_by uuid references users (id)
);

-- 5) Contact Messages
create table if not exists contact_messages (
    id uuid primary key default gen_random_uuid(),
    name text not null,
    email text not null,
    phone text,
    subject text,
    message text not null,
    status contact_status not null default 'new',
    created_at timestamptz not null default now(),
    handled_by uuid references users (id),
    handled_at timestamptz
);

-- 6) Service Requests
create table if not exists service_requests (
    id uuid primary key default gen_random_uuid(),
    client_id uuid not null references users (id),
    request_title text not null,
    category text,
    description text,
    budget_range text,
    expected_start date,
    status service_request_status not null default 'submitted',
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

-- 6b) Service Request Updates
create table if not exists service_request_updates (
    id uuid primary key default gen_random_uuid(),
    request_id uuid not null references service_requests (id) on delete cascade,
    author_id uuid references users (id),
    note text not null,
    created_at timestamptz not null default now()
);

-- 7) Gallery Images (Cloudinary-backed)
create table if not exists gallery_images (
    id uuid primary key default gen_random_uuid(),
    public_id text not null unique,
    secure_url text not null,
    format text,
    bytes integer,
    width integer,
    height integer,
    title text,
    caption text,
    sort_order integer,
    is_visible boolean not null default true,
    uploaded_by uuid references users (id),
    uploaded_at timestamptz not null default now(),
    deleted_at timestamptz
);

-- Helpful indexes
create index if not exists idx_service_requests_client_id
    on service_requests (client_id);

create index if not exists idx_service_requests_status
    on service_requests (status);

create index if not exists idx_contact_messages_status
    on contact_messages (status);

create index if not exists idx_gallery_images_visible_sort
    on gallery_images (is_visible, sort_order);

-- Seed data
-- Fixed UUIDs for initial users and pages to allow stable references
-- admin user id:   11111111-1111-1111-1111-111111111111
-- client user id:  22222222-2222-2222-2222-222222222222
-- home page id:    33333333-3333-3333-3333-333333333333
-- about page id:   44444444-4444-4444-4444-444444444444
-- service page id: 55555555-5555-5555-5555-555555555555

insert into users (id, username, full_name, email, password_hash, role, is_active)
values
    (
        '11111111-1111-1111-1111-111111111111',
        'admin',
        'Admin User',
        null,
        '$2y$12$AQUJGrPLukGOLS1MA.qJrOTD1J4kYmFB7thfR/wLt/Kb9OnkmNN7O',
        'admin',
        true
    ),
    (
        '22222222-2222-2222-2222-222222222222',
        'client',
        'Client User',
        null,
        '$2y$12$Sfu7mF3J8v9ggiL9rgm1L.rjGtQEdl3T.WFyLByu6eYvRSEYwd3YO',
        'client',
        true
    )
on conflict (id) do nothing;

-- Default site settings from original JSON
insert into site_settings (key, value, updated_by)
values
    (
        'site_title',
        'AIT NEXT GEN PRO & TALENT SOLUTIONS PRIVATE LIMITED',
        '11111111-1111-1111-1111-111111111111'
    ),
    ('primary_color', '#1D2D8C', '11111111-1111-1111-1111-111111111111'),
    ('secondary_color', '#5A39C7', '11111111-1111-1111-1111-111111111111'),
    ('accent_color', '#2A63D1', '11111111-1111-1111-1111-111111111111')
on conflict (key) do nothing;

-- Initial pages
insert into pages (id, slug, title, is_published, updated_by)
values
    (
        '33333333-3333-3333-3333-333333333333',
        'home',
        'Home',
        true,
        '11111111-1111-1111-1111-111111111111'
    ),
    (
        '44444444-4444-4444-4444-444444444444',
        'about',
        'About',
        true,
        '11111111-1111-1111-1111-111111111111'
    ),
    (
        '55555555-5555-5555-5555-555555555555',
        'service',
        'Our Services',
        true,
        '11111111-1111-1111-1111-111111111111'
    )
on conflict (slug) do nothing;

-- Initial page content from original JSON
insert into page_content (page_id, heading, subheading, content, highlight, updated_by)
values
    (
        '33333333-3333-3333-3333-333333333333',
        'Software, Hardware and Talent Solutions',
        'End-to-end delivery for business technology and staffing needs.',
        'We provide practical software engineering, dependable hardware supply, and deployment support tailored to your timelines and budgets.',
        'One team for planning, implementation, and post-deployment support.',
        '11111111-1111-1111-1111-111111111111'
    ),
    (
        '44444444-4444-4444-4444-444444444444',
        'About AIT Next Gen Pro',
        'Focused on quality delivery with transparent execution.',
        'AIT Next Gen Pro & Talent Solutions Private Limited supports clients with software development, IT infrastructure, and professional talent requirements.',
        'Business-first solutions with measurable results and long-term support.',
        '11111111-1111-1111-1111-111111111111'
    ),
    (
        '55555555-5555-5555-5555-555555555555',
        'Our Services',
        'Software, hardware, manpower, and operational support.',
        E'Custom Software Development
Hardware Supply and Installation
IT AMC and Support Services
Cloud and Infrastructure Consulting
Talent Staffing and Deployment',
        'Single-point ownership from requirement to delivery.',
        '11111111-1111-1111-1111-111111111111'
    )
on conflict (page_id) do nothing;

