// Ícone de uma categoria: a imagem enviada no painel ou, se não houver, um ícone padrão
// servido pelo próprio site (public/assets/images/categories).
const DEFAULT_ICONS = {
    crash: 'crash',
    cartas: 'cartas',
    'ao-vivo': 'ao-vivo',
    popular: 'popular',
    populares: 'popular',
    originais: 'originais',
    esportes: 'esportes',
    slots: 'slots',
    jogos: 'jogos',
};

function slugify(value) {
    return String(value || '')
        .normalize('NFD')
        .replace(/[̀-ͯ]/g, '')
        .toLowerCase()
        .trim()
        .replace(/\s+/g, '-');
}

export function categoryImage(category) {
    const image = category && category.image;
    if (image) {
        return /^(https?:)?\/\//.test(image) || image.startsWith('/') ? image : '/storage/' + image;
    }
    const icon = DEFAULT_ICONS[slugify(category && (category.slug || category.name))] || 'default';
    return `/assets/images/categories/${icon}.svg`;
}
