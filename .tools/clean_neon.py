import psycopg2

url = 'postgresql://neondb_owner:npg_5VS0xBqGUPgE@ep-raspy-truth-atmgit1k-pooler.c-9.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require'

conn = psycopg2.connect(url)
cur = conn.cursor()
print("Dropping public schema cleanly...")
cur.execute("DROP SCHEMA IF EXISTS public CASCADE;")
cur.execute("CREATE SCHEMA public;")
cur.execute("GRANT ALL ON SCHEMA public TO neondb_owner;")
cur.execute("GRANT ALL ON SCHEMA public TO public;")
cur.execute("CREATE EXTENSION IF NOT EXISTS vector;")
conn.commit()
print("Neon DB public schema completely reset and vector extension verified!")
cur.close()
conn.close()
