import subprocess
from flask import Flask, jsonify
from flask_cors import CORS  

app = Flask(__name__)

# Enable CORS for all routes and origins.
CORS(app)

@app.route('/run-scraper', methods=['POST'])
def run_script():
    try:
        result = subprocess.run(['python3', '/var/www/html/selenium_scraper.py'], capture_output=True, text=True)

        if result.returncode == 0:
            return jsonify({'success': True, 'output': result.stdout})
        else:
            return jsonify({'success': False, 'error': result.stderr})

    except Exception as e:
        return jsonify({'success': False, 'error': str(e)})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)  
