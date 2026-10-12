import { Modal } from 'flowbite';

/**
 * Modal do flowbite que não quebra a página quando o elemento ainda não existe na tela
 * (ex.: o modal de depósito, que só aparece para quem está logado).
 *
 * Aceita o id (recomendado) ou o próprio elemento. O modal de verdade é criado na primeira
 * vez que for usado e o elemento existir — inclusive se ele aparecer depois do login.
 */
export function lazyModal(target, options = {}, instanceOptions = {}) {
    let instance = null;

    const resolve = () => {
        if (instance) {
            return instance;
        }
        const el = typeof target === 'string' ? document.getElementById(target) : target;
        if (!el) {
            return null;
        }
        instance = new Modal(el, options, instanceOptions);
        return instance;
    };

    resolve();

    return {
        toggle: () => resolve()?.toggle(),
        show: () => resolve()?.show(),
        hide: () => resolve()?.hide(),
        isHidden: () => resolve()?.isHidden() ?? true,
        isVisible: () => resolve()?.isVisible() ?? false,
    };
}
