import type { ReactNode } from 'react';

interface Props {
	title: string;
	description?: ReactNode;
	actions?: ReactNode;
	tone?: 'default' | 'danger';
	children: ReactNode;
}

/**
 * A titled panel. Panels are the only raised surface on the screen.
 *
 * @param {Object}    props             Component props.
 * @param {string}    props.title       Panel heading.
 * @param {ReactNode} props.description Optional text under the heading.
 * @param {ReactNode} props.actions     Optional controls beside the heading.
 * @param {string}    props.tone        "default" or "danger".
 * @param {ReactNode} props.children    Panel content.
 */
export const Section = ( {
	title,
	description,
	actions,
	tone = 'default',
	children,
}: Props ) => (
	<section className={ `amcpb-section amcpb-section--${ tone }` }>
		<header className="amcpb-section__header">
			<div className="amcpb-section__titles">
				<h2 className="amcpb-section__title">{ title }</h2>
				{ description && (
					<p className="amcpb-section__description">
						{ description }
					</p>
				) }
			</div>
			{ actions && (
				<div className="amcpb-section__actions">{ actions }</div>
			) }
		</header>
		<div className="amcpb-section__body">{ children }</div>
	</section>
);
