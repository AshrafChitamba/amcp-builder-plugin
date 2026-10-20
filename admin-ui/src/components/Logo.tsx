/**
 * The AMCP mark: an "A" whose three points are connection nodes.
 *
 * @param {Object} props      Component props.
 * @param {number} props.size Width and height in pixels.
 */
export const Logo = ( { size = 36 }: { size?: number } ) => (
	<svg
		className="amcpb-logo"
		width={ size }
		height={ size }
		viewBox="0 0 40 40"
		aria-hidden="true"
		focusable="false"
	>
		<rect width="40" height="40" rx="10" className="amcpb-logo__tile" />
		<path
			className="amcpb-logo__mark"
			d="M18.1 10h3.8l7.3 18.4h-3.9l-1.6-4.2h-7.4l-1.6 4.2h-3.9zm1.9 4.8-2.5 6.5h5z"
		/>
		<circle className="amcpb-logo__node" cx="20" cy="9.6" r="2.3" />
		<circle className="amcpb-logo__node" cx="10.6" cy="29.6" r="2.3" />
		<circle className="amcpb-logo__node" cx="29.4" cy="29.6" r="2.3" />
	</svg>
);
