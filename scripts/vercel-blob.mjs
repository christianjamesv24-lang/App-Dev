import { del, put } from '@vercel/blob';
import { readFile } from 'node:fs/promises';

const [operation, pathname, contentType, sourcePath] = process.argv.slice(2);

try {
  if (operation === 'put') {
    const blob = await put(pathname, await readFile(sourcePath), {
      access: 'public',
      addRandomSuffix: false,
      contentType,
    });
    process.stdout.write(JSON.stringify({ url: blob.url }));
  } else if (operation === 'delete') {
    await del(pathname);
    process.stdout.write(JSON.stringify({ deleted: true }));
  } else {
    throw new Error('Unsupported operation.');
  }
} catch (error) {
  console.error(error instanceof Error ? error.message : 'Unknown Blob error.');
  process.exitCode = 1;
}
