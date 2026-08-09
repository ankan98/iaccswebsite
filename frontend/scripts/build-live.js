const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const frontendDir = process.cwd();
const projectRootDir = path.resolve(frontendDir, '..');
const rootBuildDir = path.join(projectRootDir, 'build');
const frontendOutDir = path.join(frontendDir, 'out');
const backendDir = path.join(projectRootDir, 'backend');

const skipUploads = process.argv.includes('--no-uploads') || process.argv.includes('--skip-uploads');

function copyRecursiveSync(src, dest) {
  if (!fs.existsSync(src)) return;
  const stats = fs.statSync(src);
  if (stats.isDirectory()) {
    if (skipUploads && path.basename(src) === 'uploads') {
      console.log(`⏩ Skipping ${src} (--no-uploads flag active)`);
      return;
    }
    if (!fs.existsSync(dest)) {
      fs.mkdirSync(dest, { recursive: true });
    }
    fs.readdirSync(src).forEach((childItemName) => {
      copyRecursiveSync(
        path.join(src, childItemName),
        path.join(dest, childItemName)
      );
    });
  } else {
    const destDir = path.dirname(dest);
    if (!fs.existsSync(destDir)) {
      fs.mkdirSync(destDir, { recursive: true });
    }
    fs.copyFileSync(src, dest);
  }
}

function clearDirectoryContentsSync(dir) {
  if (!fs.existsSync(dir)) {
    fs.mkdirSync(dir, { recursive: true });
    return;
  }
  for (const item of fs.readdirSync(dir)) {
    const itemPath = path.join(dir, item);
    try {
      fs.rmSync(itemPath, { recursive: true, force: true });
    } catch (e) {
      console.warn(`Could not remove ${itemPath}: ${e.message}`);
    }
  }
}

console.log('🚀 Starting Next.js static build...');
execSync('npm run build', { stdio: 'inherit', cwd: frontendDir });

console.log(`🧹 Clearing contents of root build directory: ${rootBuildDir}`);
clearDirectoryContentsSync(rootBuildDir);

console.log(`📦 Copying generated frontend static assets (from out/) to root build folder...`);
if (fs.existsSync(frontendOutDir)) {
  copyRecursiveSync(frontendOutDir, rootBuildDir);
} else {
  console.error('❌ Error: out/ directory does not exist after build!');
  process.exit(1);
}

console.log(`📂 Copying full backend folder contents to root build folder...`);
if (fs.existsSync(backendDir)) {
  copyRecursiveSync(backendDir, rootBuildDir);
} else {
  console.warn('⚠️ Warning: backend directory not found!');
}

console.log('✨ build-live complete! Output generated at: ' + rootBuildDir);
