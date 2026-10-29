// Images by file name without extension, for <Image>/<Picture> from astro:assets.
// src/assets/strapi/: content images (names used in src/data/*.json); src/assets/img/: logos and fixed images.
const arquivos = import.meta.glob('../assets/{strapi,img}/*.{jpg,jpeg,png,webp}', { eager: true, import: 'default' });

export const imagem = (nome) => {
  const encontrada = Object.entries(arquivos).find(([caminho]) => caminho.replace(/^.*\/|\.\w+$/g, '') === nome);
  if (!encontrada) throw new Error(`Imagem não encontrada em src/assets/: ${nome}`);
  return encontrada[1];
};
