import { createRoot } from 'react-dom/client';
import { Workspace } from '@/components/workspace';
import { AuthForm } from '@/components/auth-form';
import { ConfirmationDialog, ResultControls, TerDialog } from '@/components/tax-widgets';

const components = { workspace:Workspace, auth:AuthForm, confirmation:ConfirmationDialog, results:ResultControls, ter:TerDialog };
for (const element of document.querySelectorAll('[data-shadcn]')) {
    const Component = components[element.dataset.shadcn];
    if (!Component) continue;
    try {
        const props = JSON.parse(element.dataset.props ?? '{}');
        createRoot(element).render(<Component {...props} />);
    } catch (error) {
        console.error('TaxCore UI:', error);
    }
}
