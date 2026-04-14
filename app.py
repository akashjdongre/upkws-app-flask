from flask import Flask, render_template


app = Flask(__name__)
@app.route('/')
def index():
    return render_template('index.html')

@app.route('/karhandla-gate')
def karhandla_gate():
    return render_template('karhandla-gate.html')


@app.route('/gothangaon-gate')
def gothangaon_gate():
    return render_template('gothangaon-gate.html')

@app.route('/pauni-gate')
def pauni_gate():
    return render_template('pauni-gate.html')

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)