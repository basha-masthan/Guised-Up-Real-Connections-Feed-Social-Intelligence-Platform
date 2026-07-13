import urllib.request
import zipfile
import os
import shutil
import re

os.makedirs('.tools/php', exist_ok=True)
zip_path = '.tools/php.zip'

if not os.path.exists('.tools/php/php.exe'):
    print('Finding latest PHP 8.3 zip...')
    base_url = 'https://windows.php.net/downloads/releases/'
    req = urllib.request.Request(base_url, headers={'User-Agent': 'Mozilla/5.0'})
    with urllib.request.urlopen(req) as response:
        html = response.read().decode('utf-8')
    
    # Find zip like php-8.3.x-Win32-vs16-x64.zip or php-8.2 or php-8.4
    matches = re.findall(r'href="(php-8\.[234]\.\d+-Win32-vs16-x64\.zip)"', html)
    if not matches:
        # Check archives if not found
        base_url = 'https://windows.php.net/downloads/releases/archives/'
        req = urllib.request.Request(base_url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req) as response:
            html = response.read().decode('utf-8')
        matches = re.findall(r'href="(php-8\.[234]\.\d+-Win32-vs16-x64\.zip)"', html)
    
    if matches:
        zip_name = sorted(matches, reverse=True)[0]
        url = base_url + zip_name
        print(f'Downloading {url}...')
        urllib.request.urlretrieve(url, zip_path)
        print('Extracting PHP...')
        with zipfile.ZipFile(zip_path, 'r') as zip_ref:
            zip_ref.extractall('.tools/php')
        os.remove(zip_path)
        print('PHP extracted successfully.')
    else:
        print('Error: Could not find PHP zip.')

ini_path = '.tools/php/php.ini'
if not os.path.exists(ini_path) and os.path.exists('.tools/php/php.ini-development'):
    shutil.copy('.tools/php/php.ini-development', ini_path)
    extensions = [
        'extension_dir = ext',
        'extension=curl',
        'extension=fileinfo',
        'extension=mbstring',
        'extension=openssl',
        'extension=pdo_pgsql',
        'extension=pdo_sqlite',
        'extension=pgsql',
        'extension=sqlite3',
        'extension=zip',
        'extension=sockets'
    ]
    with open(ini_path, 'a') as f:
        f.write('\n' + '\n'.join(extensions) + '\n')
    print('php.ini configured.')

composer_path = '.tools/composer.phar'
if not os.path.exists(composer_path):
    print('Downloading composer.phar...')
    urllib.request.urlretrieve('https://getcomposer.org/composer-stable.phar', composer_path)
    print('Composer downloaded successfully.')
