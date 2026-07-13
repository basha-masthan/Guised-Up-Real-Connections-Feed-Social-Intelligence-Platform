import requests

print("Grabbing test bearer token from Laravel API Server...")
r = requests.get('http://127.0.0.1:8000/api/test-token')
data = r.json()
print("Token Response:", data['success'], "| User:", data['user']['name'])
token = data['token']

print("\nFetching personalized feed via GET /api/feed with Bearer token...")
headers = {'Authorization': f'Bearer {token}'}
r2 = requests.get('http://127.0.0.1:8000/api/feed?page=1', headers=headers)
feed = r2.json()
print("Feed Success:", feed['success'], "| Total posts on page 1:", len(feed['data']))
for p in feed['data'][:4]:
    print(f" - ID: {p['id']} | Author: {p['author']['name']} | Score: {p['authenticity_score']} | Time: {p['time_ago']}")

print("\nTesting semantic search via GET /api/search?q=startup+burnout...")
r3 = requests.get('http://127.0.0.1:8000/api/search?q=startup+burnout', headers=headers)
search_data = r3.json()
print("Search Success:", search_data['success'], "| Results:", len(search_data['data']))
for sp in search_data['data'][:2]:
    print(f" -> Match Score: {sp.get('similarity_score', 'N/A')} | Content snippet: {sp['content'][:50]}...")
