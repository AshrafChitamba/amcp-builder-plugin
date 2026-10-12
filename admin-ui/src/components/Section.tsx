import { Card, CardBody, CardHeader } from "@wordpress/components";
import type { ReactNode } from "react";

interface Props {
  title: string;
  description?: ReactNode;
  children: ReactNode;
}

export const Section = ({ title, description, children }: Props) => (
  <Card className="mcpb-section">
    <CardHeader>
      <h2>{title}</h2>
    </CardHeader>
    <CardBody>
      {description && <p className="mcpb-help">{description}</p>}
      {children}
    </CardBody>
  </Card>
);
