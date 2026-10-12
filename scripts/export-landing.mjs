import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { copyFile, mkdir, readFile, readdir, writeFile } from 'node:fs/promises';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const project = join(root, 'landing');
const output = join(project, 'public');
const digest = data => createHash('sha256').update(data).digest('hex');
const sources = [
    'resources/views/marketing/landing.blade.php', 'resources/views/marketing/layout.blade.php',
    'app/Http/Controllers/Web/LandingController.php', 'config/modules.php',
    'public/css/marketing.css', 'public/js/marketing-configurator.js',
    'scripts/render-public-landing.php',
];
const images = (await readdir(join(root, 'public/images/marketing'))).filter(name => /\.(png|webp|jpe?g)$/.test(name)).sort();
const assets = ['css/marketing.css', 'js/marketing-configurator.js', ...images.map(name => 'images/marketing/' + name)];
const sourceHashes = {};
for (const path of [...sources, ...images.map(name => 'public/images/marketing/' + name)]) {
    sourceHashes[path] = digest(await readFile(join(root, path)));
}
if (process.argv.includes('--check')) {
    const manifest = JSON.parse(await readFile(join(project, 'source-manifest.json'), 'utf8'));
    if (JSON.stringify(sourceHashes) !== JSON.stringify(manifest.sources)) throw new Error('Landing export is outdated. Run npm run build:landing before publishing.');
    for (const [path, hash] of Object.entries(manifest.outputs)) {
        if (digest(await readFile(join(output, path))) !== hash) throw new Error('Exported asset mismatch: ' + path);
    }
    console.log('Landing export verified: sources, HTML and ' + assets.length + ' assets.');
} else {
    const html = execFileSync(process.env.LANDING_PHP_BINARY || 'php', [join(root, 'scripts/render-public-landing.php')], { cwd: root, encoding: 'utf8', maxBuffer: 2 * 1024 * 1024 });
    if (!html.includes('id="configurable"') || !html.includes('moduleConfigurator(')) throw new Error('Required configurable section missing.');
    await mkdir(output, { recursive: true });
    await writeFile(join(output, 'index.html'), html);
    for (const asset of assets) {
        await mkdir(dirname(join(output, asset)), { recursive: true });
        await copyFile(join(root, 'public', asset), join(output, asset));
    }
    await writeFile(join(output, 'robots.txt'), 'User-agent: *\nAllow: /\n');
    const outputs = {};
    for (const path of ['index.html', 'robots.txt', ...assets]) outputs[path] = digest(await readFile(join(output, path)));
    await writeFile(join(project, 'source-manifest.json'), JSON.stringify({ sources: sourceHashes, outputs }, null, 2) + '\n');
    console.log('Public landing exported to landing/public; no application routes or tenant data.');
}
