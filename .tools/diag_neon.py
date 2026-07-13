import psycopg2

url = 'postgresql://neondb_owner:npg_5VS0xBqGUPgE@ep-raspy-truth-atmgit1k-pooler.c-9.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require'
conn = psycopg2.connect(url)
cur = conn.cursor()
cur.execute("DROP TABLE IF EXISTS users CASCADE;")
conn.commit()

try:
    print("Executing CREATE TABLE users...")
    cur.execute('create table "users" ("id" bigserial not null primary key, "name" varchar(255) not null, "email" varchar(255) not null, "avatar_url" varchar(1024) null, "email_verified_at" timestamp(0) without time zone null, "password" varchar(255) not null, "remember_token" varchar(100) null, "created_at" timestamp(0) without time zone null, "updated_at" timestamp(0) without time zone null);')
    print("Executing ALTER TABLE users ADD CONSTRAINT...")
    cur.execute('alter table "users" add constraint "users_email_unique" unique ("email");')
    conn.commit()
    print("SUCCESS!")
except Exception as e:
    print("ERROR IN TRANSACTION:", e)
cur.close()
conn.close()
