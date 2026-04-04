import os
import subprocess
from pathlib import Path

# Path to your dev directory
DEV_DIR = os.path.expanduser('~/dev')
PLUGIN_PATH = 'wp-content/plugins/sog-unc-rebrand'
REPO_URL = 'git@sc.unc.edu:sog-it/sog-unc-rebrand.git'
BRANCH = 'main'

ADD_CMD = [
    'git', 'subtree', 'add', f'--prefix={PLUGIN_PATH}', REPO_URL, BRANCH, '--squash'
]
PULL_CMD = [
    'git', 'subtree', 'pull', f'--prefix={PLUGIN_PATH}', REPO_URL, BRANCH, '--squash'
]

def is_wordpress_codebase(path):
    return (path / 'wp-content' / 'plugins').is_dir()

def has_sog_plugin(path):
    return (path / PLUGIN_PATH).is_dir()

def run_git_command(cmd, cwd):
    try:
        subprocess.run(cmd, cwd=cwd, check=True)
        print(f"Success: {' '.join(cmd)} in {cwd}")
    except subprocess.CalledProcessError as e:
        print(f"Error: {' '.join(cmd)} in {cwd}\n{e}")

def main():
    dev_path = Path(DEV_DIR)
    for project in dev_path.iterdir():
        if not project.is_dir():
            continue
        if is_wordpress_codebase(project):
            print(f"\nProcessing {project}...")
            if has_sog_plugin(project):
                print("sog-unc-rebrand found, updating...")
                run_git_command(PULL_CMD, cwd=project)
            else:
                print("sog-unc-rebrand not found, adding...")
                run_git_command(ADD_CMD, cwd=project)

if __name__ == '__main__':
    main()
