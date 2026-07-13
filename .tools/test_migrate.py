import psycopg2
import subprocess
import os

url = 'postgresql://neondb_owner:npg_5VS0xBqGUPgE@ep-raspy-truth-atmgit1k-pooler.c-9.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require'

conn = psycopg2.connect(url)
cur = conn.cursor()
print("Dropping all tables in public schema cleanly...")
cur.execute("DROP SCHEMA IF EXISTS public CASCADE; CREATE SCHEMA public; GRANT ALL ON SCHEMA public TO neondb_owner; GRANT ALL ON SCHEMA public TO public; CREATE EXTENSION IF NOT EXISTS vector;")
conn.commit()
print("Public schema reset.")
cur.close()
conn.close()

os.chdir('backend/laravel-app')
env = os.environ.copy()
env['DB_CONNECTION'] = 'pgsql'
env['DATABASE_URL'] = url

print("Running php artisan migrate -v...")
proc = subprocess.run(['../../.tools/php/php.exe', 'artisan', 'migrate', '-v', '--force'], capture_output=True, text=True, env=env)
print("STDOUT:", proc.stdout)
print("STDERR:", proc.stderr)
